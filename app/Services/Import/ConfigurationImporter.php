<?php

namespace App\Services\Import;

use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Models\User;
use App\Services\PlatformDetection\PlatformDetector;
use App\Services\Storage\ConfigurationFileStorage;
use App\Services\Storage\SafeZipExtractor;
use App\Services\Storage\StoredConfiguration;
use App\Services\Xml\XmlValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

final readonly class ConfigurationImporter
{
    public function __construct(
        private ConfigurationFileStorage $fileStorage,
        private PlatformDetector $platformDetector,
        private XmlValidator $xmlValidator,
        private SafeZipExtractor $zipExtractor,
    ) {}

    public function import(Project $project, UploadedFile $file, User $user): ConfigurationImport
    {
        if ($project->user_id !== $user->id) {
            throw new RuntimeException('The selected project does not belong to the signed-in user.');
        }

        $stored = $this->fileStorage->store($file, $project->id);

        try {
            if (strtolower(pathinfo($stored->path, PATHINFO_EXTENSION)) === 'zip') {
                return $this->importArchive($project, $stored, $user);
            }
            [$contents, $paths, $validationErrors] = $this->inspect($stored);
            $detection = $this->platformDetector->detect(implode("\n", $contents), $paths);

            return DB::transaction(function () use (
                $project,
                $user,
                $stored,
                $detection,
                $validationErrors,
            ): ConfigurationImport {
                $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
                $import = $lockedProject->imports()->create([
                    'original_filename' => $stored->originalFilename,
                    'storage_path' => $stored->path,
                    'sha256' => $stored->sha256,
                    'detected_platform' => $detection->platform,
                    'detection_confidence' => $detection->confidence,
                    'validation_status' => $validationErrors === [] ? 'valid' : 'invalid',
                    'validation_errors' => $validationErrors ?: null,
                    'imported_at' => now(),
                ]);

                $revisionNumber = ((int) $lockedProject->revisions()->max('revision_number')) + 1;
                ConfigurationRevision::query()->create([
                    'project_id' => $lockedProject->id,
                    'configuration_import_id' => $import->id,
                    'revision_number' => $revisionNumber,
                    'storage_path' => $stored->path,
                    'sha256' => $stored->sha256,
                    'change_summary' => "Import souboru {$stored->originalFilename}",
                    'created_by' => $user->id,
                ]);

                if ($lockedProject->platform === 'unknown' && $detection->platform !== 'unknown') {
                    $lockedProject->update([
                        'platform' => $detection->platform,
                        'platform_confidence' => $detection->confidence,
                    ]);
                } elseif ($lockedProject->platform_confidence === null) {
                    $lockedProject->update(['platform_confidence' => $detection->confidence]);
                }

                return $import;
            });
        } catch (Throwable $exception) {
            Storage::disk('dayz')->delete($stored->path);

            throw $exception;
        }
    }

    /**
     * Creates a brand-new configuration file (and its first revision) directly from generated
     * content, without going through an upload — used to seed an empty *_territories.xml when a
     * newly registered animal in cfgenvironment.xml has no existing territory file to link to.
     */
    public function importGeneratedFile(Project $project, string $filename, string $content, User $user): ConfigurationImport
    {
        if ($project->user_id !== $user->id) {
            throw new RuntimeException('The selected project does not belong to the signed-in user.');
        }

        $validationErrors = $this->validate($filename, $content);
        if ($validationErrors !== []) {
            throw new RuntimeException($validationErrors[0]['message'] ?? 'Generovaný soubor není platný.');
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $path = "{$project->id}/imports/".Str::uuid().'.'.$extension;
        Storage::disk('dayz')->put($path, $content);

        try {
            return DB::transaction(function () use ($project, $user, $filename, $content, $path): ConfigurationImport {
                $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
                $import = $lockedProject->imports()->create([
                    'original_filename' => $filename,
                    'storage_path' => $path,
                    'sha256' => hash('sha256', $content),
                    'detected_platform' => 'unknown',
                    'detection_confidence' => 0,
                    'validation_status' => 'valid',
                    'validation_errors' => null,
                    'imported_at' => now(),
                ]);

                $revisionNumber = ((int) $lockedProject->revisions()->max('revision_number')) + 1;
                ConfigurationRevision::query()->create([
                    'project_id' => $lockedProject->id,
                    'configuration_import_id' => $import->id,
                    'revision_number' => $revisionNumber,
                    'storage_path' => $path,
                    'sha256' => hash('sha256', $content),
                    'change_summary' => "Založen nový soubor {$filename}",
                    'created_by' => $user->id,
                ]);

                return $import;
            });
        } catch (Throwable $exception) {
            Storage::disk('dayz')->delete($path);

            throw $exception;
        }
    }

    private function importArchive(Project $project, StoredConfiguration $archive, User $user): ConfigurationImport
    {
        [$contents, $paths] = $this->inspect($archive);
        $storedPaths = [];
        try {
            $lastImport = DB::transaction(function () use ($project, $user, $contents, $paths, &$storedPaths): ConfigurationImport {
                $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
                $revisionNumber = (int) $lockedProject->revisions()->max('revision_number');
                $lastImport = null;
                foreach ($contents as $index => $content) {
                    $originalFilename = str_replace('\\', '/', $paths[$index]);
                    $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                    $path = "{$project->id}/imports/".Str::uuid().'.'.$extension;
                    Storage::disk('dayz')->put($path, $content);
                    $storedPaths[] = $path;
                    $validationErrors = $this->validate($originalFilename, $content);
                    $detection = $this->platformDetector->detect($content, [$originalFilename]);
                    $lastImport = $lockedProject->imports()->create([
                        'original_filename' => $originalFilename,
                        'storage_path' => $path,
                        'sha256' => hash('sha256', $content),
                        'detected_platform' => $detection->platform,
                        'detection_confidence' => $detection->confidence,
                        'validation_status' => $validationErrors === [] ? 'valid' : 'invalid',
                        'validation_errors' => $validationErrors ?: null,
                        'imported_at' => now(),
                    ]);
                    ConfigurationRevision::query()->create([
                        'project_id' => $lockedProject->id,
                        'configuration_import_id' => $lastImport->id,
                        'revision_number' => ++$revisionNumber,
                        'storage_path' => $path,
                        'sha256' => hash('sha256', $content),
                        'change_summary' => "Import souboru {$originalFilename} ze ZIP balíku",
                        'created_by' => $user->id,
                    ]);
                }
                if (! $lastImport) throw new RuntimeException('ZIP archiv neobsahuje podporované konfigurační soubory.');
                return $lastImport;
            });
            Storage::disk('dayz')->delete($archive->path);
            return $lastImport;
        } catch (Throwable $exception) {
            Storage::disk('dayz')->delete($storedPaths);
            throw $exception;
        }
    }

    /**
     * @return array{0: list<string>, 1: list<string>, 2: list<array<string, mixed>>}
     */
    private function inspect(StoredConfiguration $stored): array
    {
        $extension = strtolower(pathinfo($stored->path, PATHINFO_EXTENSION));
        $absolutePath = Storage::disk('dayz')->path($stored->path);

        if ($extension !== 'zip') {
            $content = Storage::disk('dayz')->get($stored->path);

            return [[$content], [$stored->originalFilename], $this->validate($stored->originalFilename, $content)];
        }

        $extractPath = dirname($absolutePath).DIRECTORY_SEPARATOR.'extracted-'.substr($stored->sha256, 0, 12);
        $extractedFiles = $this->zipExtractor->extract($absolutePath, $extractPath);
        if ($extractedFiles === []) {
            throw new RuntimeException('ZIP archive does not contain any XML or JSON configuration files.');
        }

        $contents = [];
        $paths = [];
        $errors = [];

        foreach ($extractedFiles as $extractedFile) {
            $relativePath = str_replace('\\', '/', substr($extractedFile, strlen($extractPath) + 1));
            $content = file_get_contents($extractedFile);
            if ($content === false) {
                throw new RuntimeException("Unable to read extracted file: {$relativePath}");
            }

            $contents[] = $content;
            $paths[] = $relativePath;
            array_push($errors, ...$this->validate($relativePath, $content));
        }

        return [$contents, $paths, $errors];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function validate(string $filename, string $content): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'xml') {
            $result = $this->xmlValidator->validate($content);

            return array_map(
                static fn (array $error): array => ['file' => $filename, ...$error],
                $result->errors,
            );
        }

        if (in_array($extension, ['cfg', 'txt', 'c'], true)) {
            return [];
        }

        try {
            json_decode($content, true, flags: JSON_THROW_ON_ERROR);

            return [];
        } catch (JsonException $exception) {
            return [[
                'file' => $filename,
                'line' => 0,
                'message' => $exception->getMessage(),
            ]];
        }
    }
}
