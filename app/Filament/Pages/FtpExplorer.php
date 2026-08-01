<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Ftp\FtpBrowser;
use App\Services\Import\ConfigurationImporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Throwable;

class FtpExplorer extends Page
{
    protected static string $view = 'filament.pages.ftp-explorer';

    protected static bool $shouldRegisterNavigation = false;

    public array $projects = [];

    public ?int $projectId = null;

    public string $currentPath = '';

    public array $entries = [];

    public ?string $errorMessage = null;

    public bool $connected = false;

    /** @var array{imported: list<string>, failed: list<string>}|null */
    public ?array $lastImportSummary = null;

    public function mount(): void
    {
        $this->projects = $this->projectQuery()->orderBy('name')->pluck('name', 'id')->all();
        $requestedProject = request()->integer('project');
        $this->projectId = array_key_exists($requestedProject, $this->projects)
            ? $requestedProject
            : array_key_first($this->projects);
        $this->currentPath = trim((string) request()->string('path'), '/');
        $this->loadDirectory();
    }

    public function updatedProjectId(): void
    {
        $this->currentPath = '';
        $this->loadDirectory();
    }

    public function open(string $path): void
    {
        $this->currentPath = trim($path, '/');
        $this->loadDirectory();
    }

    public function up(): void
    {
        $segments = array_filter(explode('/', $this->currentPath));
        array_pop($segments);
        $this->currentPath = implode('/', $segments);
        $this->loadDirectory();
    }

    public function importFile(string $path, FtpBrowser $browser, ConfigurationImporter $importer): void
    {
        $this->lastImportSummary = null;
        $project = $this->currentProject();
        if (! $project) {
            return;
        }

        try {
            $import = $browser->importFile($project, $path, auth()->user(), $importer);
        } catch (Throwable $exception) {
            $message = $this->readableExceptionMessage($exception);
            $this->lastImportSummary = ['imported' => [], 'failed' => [basename($path).': '.$message]];
            Notification::make()->danger()->title('Import selhal')->body($message)->send();

            return;
        }

        $this->lastImportSummary = ['imported' => [$import->original_filename], 'failed' => []];
        $this->entries = $this->withImportHistory($this->entries, $project->fresh());
        Notification::make()->success()->title('Soubor importován')->body($import->original_filename)->send();
    }

    /** Extensions ConfigurationFileStorage actually accepts — kept in sync manually since that class has no public accessor. */
    private const IMPORTABLE_EXTENSIONS = ['xml', 'json', 'zip', 'cfg', 'txt', 'c'];

    /** Imports every importable file found under the current folder, including subfolders like env/ or custom/, in one go. */
    public function importAllInFolder(FtpBrowser $browser, ConfigurationImporter $importer): void
    {
        $this->lastImportSummary = null;
        $project = $this->currentProject();
        if (! $project) {
            return;
        }

        try {
            $filesystem = $browser->filesystem($project);
        } catch (Throwable $exception) {
            $message = $this->readableExceptionMessage($exception);
            $this->lastImportSummary = ['imported' => [], 'failed' => ['Připojení selhalo: '.$message]];
            Notification::make()->danger()->title('Připojení selhalo')->body($message)->send();

            return;
        }

        try {
            $allFiles = collect($browser->listFilesRecursiveOn($filesystem, $this->currentPath));
        } catch (Throwable $exception) {
            $message = $this->readableExceptionMessage($exception);
            $this->lastImportSummary = ['imported' => [], 'failed' => ['Načtení souborů selhalo: '.$message]];
            Notification::make()->danger()->title('Načtení souborů selhalo')->body($message)->send();

            return;
        }
        if ($allFiles->isEmpty()) {
            return;
        }

        // Skip files with an extension the importer would reject outright (e.g. Nitrado's
        // areaflags.map) instead of downloading them over FTP just to fail — this matters
        // for real servers where such a file can be tens of megabytes.
        [$files, $skipped] = $allFiles->partition(
            fn (array $entry): bool => in_array(strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION)), self::IMPORTABLE_EXTENSIONS, true)
        );

        $failed = $skipped->map(fn (array $entry): string => $entry['path'].': nepodporovaná přípona, přeskočeno')->values()->all();

        if ($files->isEmpty()) {
            $this->lastImportSummary = ['imported' => [], 'failed' => $failed];
            Notification::make()->danger()->title('Nic k importu')->body('V této složce ani jejích podsložkách není žádný soubor s podporovanou příponou.')->send();

            return;
        }

        // Reuses one FTP/SFTP connection for the whole batch instead of reconnecting per
        // file — with many files, a fresh connect+login per file is slow enough to risk
        // hitting the request timeout with no error shown at all. Also give this specific
        // action more time than the default PHP limit, since it's a known-slow bulk action.
        @set_time_limit(300);

        $importedNames = [];
        foreach ($files as $entry) {
            try {
                $import = $browser->importFileOn($filesystem, $project, $entry['path'], auth()->user(), $importer);
                $importedNames[] = $import->original_filename;
            } catch (Throwable $exception) {
                $failed[] = $entry['path'].': '.$this->readableExceptionMessage($exception);
            }
        }

        $this->lastImportSummary = ['imported' => $importedNames, 'failed' => $failed];
        if ($importedNames !== []) {
            $this->entries = $this->withImportHistory($this->entries, $project->fresh());
        }

        if ($importedNames !== []) {
            Notification::make()->success()->title(count($importedNames).'× importováno')->body(implode(', ', $importedNames))->send();
        }
        if ($failed !== []) {
            Notification::make()->danger()->title(count($failed).'× se nepodařilo importovat nebo bylo přeskočeno')->body(implode(' | ', $failed))->send();
        }
    }

    private function readableExceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return implode(' ', $exception->validator->errors()->all());
        }

        return $exception->getMessage();
    }

    public function loadDirectory(): void
    {
        $this->entries = [];
        $this->errorMessage = null;
        $this->connected = false;

        $project = $this->currentProject();
        if (! $project) {
            return;
        }

        if (! $project->hasFtpConnection()) {
            $this->errorMessage = 'Tento server nemá nastavené FTP připojení. Doplň ho v Nastavení serveru.';

            return;
        }

        try {
            $this->entries = $this->withImportHistory(
                app(FtpBrowser::class)->listDirectory($project, $this->currentPath),
                $project,
            );
            $this->connected = true;
        } catch (Throwable $exception) {
            $this->errorMessage = $this->readableExceptionMessage($exception);
        }
    }

    /**
     * Attaches "last imported at" to every file entry, matched by filename against this
     * project's own import history — so it's visible at a glance without importing again.
     *
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    private function withImportHistory(array $entries, Project $project): array
    {
        // Imports of subfolder files (env/, custom/) now keep their folder in original_filename,
        // so match on its basename — the entry list here only ever carries bare filenames.
        $latestByFilename = $project->imports
            ->groupBy(fn ($import): string => strtolower(basename(str_replace('\\', '/', $import->original_filename))))
            ->map(fn ($group) => $group->sortByDesc('imported_at')->first());

        return array_map(function (array $entry) use ($latestByFilename): array {
            if ($entry['type'] === 'file') {
                $match = $latestByFilename->get(strtolower($entry['name']));
                $entry['last_imported_at'] = $match?->imported_at?->format('d.m.Y H:i');
            }

            return $entry;
        }, $entries);
    }

    private function currentProject(): ?Project
    {
        return $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
    }

    private function projectQuery()
    {
        return Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()));
    }

    public function getTitle(): string
    {
        return 'FTP prohlížeč';
    }

    public function getSubheading(): ?string
    {
        return 'Prohlédni soubory přímo na serveru přes FTP/FTPS/SFTP a importuj je bez ručního stahování.';
    }
}
