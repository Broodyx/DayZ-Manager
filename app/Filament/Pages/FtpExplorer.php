<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Ftp\FtpBrowser;
use App\Services\Import\ConfigurationImporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use RuntimeException;

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
        } catch (RuntimeException $exception) {
            $this->lastImportSummary = ['imported' => [], 'failed' => [basename($path).': '.$exception->getMessage()]];
            Notification::make()->danger()->title('Import selhal')->body($exception->getMessage())->send();

            return;
        }

        $this->lastImportSummary = ['imported' => [$import->original_filename], 'failed' => []];
        Notification::make()->success()->title('Soubor importován')->body($import->original_filename)->send();
    }

    /** Imports every file listed in the current folder (not subfolders) in one go. */
    public function importAllInFolder(FtpBrowser $browser, ConfigurationImporter $importer): void
    {
        $this->lastImportSummary = null;
        $project = $this->currentProject();
        if (! $project) {
            return;
        }

        $files = collect($this->entries)->where('type', 'file');
        if ($files->isEmpty()) {
            return;
        }

        $importedNames = [];
        $failed = [];
        foreach ($files as $entry) {
            try {
                $import = $browser->importFile($project, $entry['path'], auth()->user(), $importer);
                $importedNames[] = $import->original_filename;
            } catch (RuntimeException $exception) {
                $failed[] = $entry['name'].': '.$exception->getMessage();
            }
        }

        $this->lastImportSummary = ['imported' => $importedNames, 'failed' => $failed];

        if ($importedNames !== []) {
            Notification::make()->success()->title(count($importedNames).'× importováno')->body(implode(', ', $importedNames))->send();
        }
        if ($failed !== []) {
            Notification::make()->danger()->title(count($failed).'× se nepodařilo importovat')->body(implode(' | ', $failed))->send();
        }
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
            $this->entries = app(FtpBrowser::class)->listDirectory($project, $this->currentPath);
            $this->connected = true;
        } catch (RuntimeException $exception) {
            $this->errorMessage = $exception->getMessage();
        }
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
