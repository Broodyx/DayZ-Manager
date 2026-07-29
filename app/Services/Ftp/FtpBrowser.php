<?php

namespace App\Services\Ftp;

use App\Models\ConfigurationImport;
use App\Models\Project;
use App\Models\User;
use App\Services\Dayz\ConfigurationCatalog;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use RuntimeException;

/**
 * Connects to a project's own FTP/FTPS/SFTP server using credentials the user entered
 * themselves in the project settings — this class never receives or uses credentials
 * typed anywhere else, and is only ever invoked from an authenticated user action.
 *
 * Every public entry point resolves its own Filesystem from the project's stored
 * connection; the *On() variants take an already-built Filesystem instead, so tests
 * can exercise the listing/classification/import logic against a local directory
 * instead of a real remote server.
 */
class FtpBrowser
{
    /** Folder names DayZ/hosting panels normally create; anything else is flagged "atypické". */
    private const KNOWN_FOLDER_NAMES = ['db', 'env', 'storage_1', 'storage_2', 'backup', 'data'];

    public function filesystem(Project $project): Filesystem
    {
        if (! $project->hasFtpConnection()) {
            throw new RuntimeException('Server nemá nastavené FTP připojení — doplň ho v Nastavení serveru.');
        }

        $protocol = $project->ftp_protocol ?: 'ftp';
        $root = $project->ftp_root_path ?: '/';

        if ($protocol === 'sftp') {
            $provider = new SftpConnectionProvider(
                host: (string) $project->ftp_host,
                username: (string) $project->ftp_username,
                password: (string) $project->ftp_password,
                port: $project->ftp_port ?: 22,
                timeout: 15,
            );

            return new Filesystem(new SftpAdapter($provider, $root));
        }

        $options = new FtpConnectionOptions(
            host: (string) $project->ftp_host,
            root: $root,
            username: (string) $project->ftp_username,
            password: (string) $project->ftp_password,
            port: $project->ftp_port ?: 21,
            ssl: $protocol === 'ftps',
            timeout: 15,
            utf8: true,
            passive: true,
        );

        return new Filesystem(new FtpAdapter($options));
    }

    /** @return list<array{name: string, path: string, type: string, size: ?int, known: bool, category: ?string}> */
    public function listDirectory(Project $project, string $path = ''): array
    {
        return $this->listDirectoryOn($this->filesystem($project), $path);
    }

    /** @return list<array{name: string, path: string, type: string, size: ?int, known: bool, category: ?string}> */
    public function listDirectoryOn(Filesystem $filesystem, string $path = ''): array
    {
        try {
            $listing = $filesystem->listContents($path)->toArray();
        } catch (UnableToListContents $exception) {
            throw new RuntimeException('Adresář se nepodařilo načíst: '.$exception->getMessage(), previous: $exception);
        }

        $entries = array_map(function (StorageAttributes $item): array {
            $name = basename($item->path());
            $classification = $item->isDir() ? $this->classifyFolder($name) : $this->classifyFile($name);

            return [
                'name' => $name,
                'path' => $item->path(),
                'type' => $item->isDir() ? 'dir' : 'file',
                'size' => $item instanceof FileAttributes ? $item->fileSize() : null,
                'known' => $classification['known'],
                'category' => $classification['category'],
            ];
        }, $listing);

        usort($entries, fn (array $a, array $b): int => $a['type'] !== $b['type']
            ? ($a['type'] === 'dir' ? -1 : 1)
            : strnatcasecmp($a['name'], $b['name']));

        return $entries;
    }

    public function read(Project $project, string $path): string
    {
        return $this->readOn($this->filesystem($project), $path);
    }

    public function readOn(Filesystem $filesystem, string $path): string
    {
        try {
            return $filesystem->read($path);
        } catch (UnableToReadFile $exception) {
            throw new RuntimeException('Soubor se nepodařilo načíst: '.$exception->getMessage(), previous: $exception);
        }
    }

    /** @return array{known: bool, category: ?string} */
    public function classifyFile(string $filename): array
    {
        $lower = strtolower($filename);
        foreach (app(ConfigurationCatalog::class)->filesByArea() as $area => $files) {
            foreach ($files as $file) {
                if (Str::is(strtolower($file['pattern']), $lower)) {
                    return ['known' => true, 'category' => $area];
                }
            }
        }

        return ['known' => false, 'category' => null];
    }

    /** @return array{known: bool, category: null} */
    public function classifyFolder(string $name): array
    {
        return ['known' => in_array(strtolower($name), self::KNOWN_FOLDER_NAMES, true), 'category' => null];
    }

    /** Writes content to a path on the remote server, overwriting whatever is there — this is a live, real deploy. */
    public function write(Project $project, string $path, string $content): void
    {
        $this->writeOn($this->filesystem($project), $path, $content);
    }

    public function writeOn(Filesystem $filesystem, string $path, string $content): void
    {
        try {
            $filesystem->write($path, $content);
        } catch (UnableToWriteFile $exception) {
            throw new RuntimeException('Soubor se nepodařilo zapsat na server: '.$exception->getMessage(), previous: $exception);
        }
    }

    /** Fetches a remote file's content and runs it through the normal import pipeline, as if it had been uploaded by hand. */
    public function importFile(Project $project, string $path, User $user, ConfigurationImporter $importer): ConfigurationImport
    {
        return $this->importFileOn($this->filesystem($project), $project, $path, $user, $importer);
    }

    public function importFileOn(Filesystem $filesystem, Project $project, string $path, User $user, ConfigurationImporter $importer): ConfigurationImport
    {
        $content = $this->readOn($filesystem, $path);
        $filename = basename($path);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'txt';

        $tempPath = tempnam(sys_get_temp_dir(), 'dzftp').'.'.$extension;
        file_put_contents($tempPath, $content);

        try {
            $uploadedFile = new UploadedFile($tempPath, $filename, null, null, true);

            return $importer->import($project, $uploadedFile, $user);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
