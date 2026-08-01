<?php

namespace App\Filament\Pages;

use App\Models\ConfigurationRevision;
use App\Models\LogAnalysis;
use App\Models\Project;
use App\Services\Dayz\MapConfigurationEditor;
use App\Services\Dayz\ServerLogAnalyzer;
use App\Services\Ftp\FtpBrowser;
use App\Services\Revision\ConfigurationRevisionEditor;
use App\Services\Revision\TypesXmlEditor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class LogAnalyzer extends Page
{
    protected static string $view = 'filament.pages.log-analyzer';

    protected static bool $shouldRegisterNavigation = false;

    public array $projects = [];

    public ?int $projectId = null;

    public string $logContent = '';

    public array $findings = [];

    public ?int $totalLines = null;

    public ?int $matchedLines = null;

    public bool $analyzed = false;

    public array $history = [];

    public ?int $viewingHistoryId = null;

    public array $ftpLogFiles = [];

    public bool $ftpLogsLoaded = false;

    public ?string $ftpLogError = null;

    public function mount(): void
    {
        $this->projects = $this->projectQuery()->orderBy('name')->pluck('name', 'id')->all();
        $requestedProject = request()->integer('project');
        $this->projectId = array_key_exists($requestedProject, $this->projects)
            ? $requestedProject
            : array_key_first($this->projects);
        $this->loadHistory();
    }

    public function updatedProjectId(): void
    {
        $this->loadHistory();
        $this->ftpLogFiles = [];
        $this->ftpLogsLoaded = false;
        $this->ftpLogError = null;
    }

    /** Lists .RPT/.ADM/.log files at the project's configured FTP log path — a separate root from the mission files on hosts (e.g. Nitrado on console) that split the two. */
    public function loadFtpLogFiles(FtpBrowser $browser): void
    {
        $this->ftpLogFiles = [];
        $this->ftpLogError = null;
        $this->ftpLogsLoaded = true;

        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project || ! $project->hasFtpLogConnection()) {
            return;
        }

        try {
            $this->ftpLogFiles = $browser->listLogFiles($project);
        } catch (RuntimeException $exception) {
            $this->ftpLogError = $exception->getMessage();
        }
    }

    /** Fetches one log file's content from FTP straight into the analyzer textarea. */
    public function loadFtpLogFile(string $path, FtpBrowser $browser): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }

        try {
            $this->logContent = $browser->readLogFile($project, $path);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title('Log se nepodařilo načíst')->body($exception->getMessage())->send();

            return;
        }

        $this->analyzed = false;
        Notification::make()->success()->title('Log načten z FTP')->body(basename($path))->send();
    }

    public function loadHistory(): void
    {
        $this->history = LogAnalysis::query()
            ->where('created_by', auth()->id())
            ->when($this->projectId, fn ($query) => $query->where('project_id', $this->projectId))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (LogAnalysis $item): array => [
                'id' => $item->id,
                'created_at' => $item->created_at->format('d.m.Y H:i'),
                'total_lines' => $item->total_lines,
                'matched_lines' => $item->matched_lines,
                'critical_count' => $item->critical_count,
                'warning_count' => $item->warning_count,
            ])
            ->all();
    }

    public function analyze(ServerLogAnalyzer $analyzer): void
    {
        $this->analyzed = true;
        $this->viewingHistoryId = null;
        if (trim($this->logContent) === '') {
            $this->findings = [];
            $this->totalLines = 0;
            $this->matchedLines = 0;

            return;
        }
        $result = $analyzer->analyze($this->logContent);
        $this->findings = $result['findings'];
        $this->totalLines = $result['totalLines'];
        $this->matchedLines = $result['matchedLines'];

        $this->saveHistory($result);
    }

    private function saveHistory(array $result): void
    {
        $path = 'log-analyses/'.auth()->id().'/'.now()->format('Ymd-His').'-'.Str::random(8).'.log';
        Storage::disk('dayz')->put($path, $this->logContent);

        $counts = collect($result['findings'])->countBy('severity');

        LogAnalysis::query()->create([
            'project_id' => $this->projectId,
            'created_by' => auth()->id(),
            'storage_path' => $path,
            'findings' => $result['findings'],
            'total_lines' => $result['totalLines'],
            'matched_lines' => $result['matchedLines'],
            'critical_count' => $counts->get('critical', 0),
            'warning_count' => $counts->get('warning', 0),
        ]);

        $this->loadHistory();
    }

    public function loadFromHistory(int $id): void
    {
        $item = LogAnalysis::query()->where('created_by', auth()->id())->find($id);
        if (! $item) {
            return;
        }

        $this->logContent = Storage::disk('dayz')->exists($item->storage_path)
            ? Storage::disk('dayz')->get($item->storage_path)
            : '';
        $this->findings = $item->findings;
        $this->totalLines = $item->total_lines;
        $this->matchedLines = $item->matched_lines;
        $this->analyzed = true;
        $this->viewingHistoryId = $item->id;
    }

    public function deleteHistory(int $id): void
    {
        $item = LogAnalysis::query()->where('created_by', auth()->id())->find($id);
        if (! $item) {
            return;
        }
        if (Storage::disk('dayz')->exists($item->storage_path)) {
            Storage::disk('dayz')->delete($item->storage_path);
        }
        $item->delete();
        if ($this->viewingHistoryId === $id) {
            $this->viewingHistoryId = null;
        }
        $this->loadHistory();
        Notification::make()->success()->title('Analýza odstraněna z historie.')->send();
    }

    public function clear(): void
    {
        $this->reset(['logContent', 'findings', 'totalLines', 'matchedLines', 'analyzed', 'viewingHistoryId']);
    }

    public function mapEditorUrl(?string $openEvent = null): string
    {
        $query = [];
        if ($this->projectId) {
            $query['project'] = $this->projectId;
        }
        if ($openEvent !== null && $openEvent !== '') {
            $query['open_event'] = $openEvent;
        }

        return url('/admin/map-editor').($query ? '?'.http_build_query($query) : '');
    }

    public function typesEditorUrl(?string $typeName): ?string
    {
        if (! $this->projectId || ! $typeName) {
            return null;
        }

        return url('/admin/projects/'.$this->projectId.'/configuration').'?'.http_build_query(['type' => $typeName]);
    }

    /** Removes a types.xml entry directly — safe because the game already refuses to spawn it (typo/missing mod/private scope). */
    public function removeTypeEntry(string $typeName, TypesXmlEditor $typesEditor, ConfigurationRevisionEditor $revisionEditor): void
    {
        $revision = $this->latestRevisionByFilename('types.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            Notification::make()->danger()->title('types.xml nebyl pro tento server nalezen.')->send();

            return;
        }

        try {
            $content = $typesEditor->remove(Storage::disk('dayz')->get($revision->storage_path), $typeName);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        $saved = $revisionEditor->save(
            $this->projectQuery()->find($this->projectId),
            $revision,
            $content,
            "Odebrána položka {$typeName} (log analyzátor)",
            auth()->user(),
        );
        Notification::make()->success()->title("Položka {$typeName} odebrána z types.xml")->body("Vznikla revize #{$saved->revision_number}.")->send();
    }

    /** Removes orphaned cfgeventspawns.xml positions for an event events.xml does not define — safe because they currently do nothing. */
    public function removeOrphanEventSpawn(string $eventName, MapConfigurationEditor $mapEditor, ConfigurationRevisionEditor $revisionEditor): void
    {
        $revision = $this->latestRevisionByFilename('cfgeventspawns.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            Notification::make()->danger()->title('cfgeventspawns.xml nebyl pro tento server nalezen.')->send();

            return;
        }

        try {
            $result = $mapEditor->deleteScope('cfgeventspawns.xml', Storage::disk('dayz')->get($revision->storage_path), 'event:'.$eventName);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        $saved = $revisionEditor->save(
            $this->projectQuery()->find($this->projectId),
            $revision,
            $result['content'],
            "Odstraněny pozice eventu {$eventName} (log analyzátor)",
            auth()->user(),
        );
        Notification::make()->success()->title("Pozice eventu {$eventName} odstraněny")->body("Vznikla revize #{$saved->revision_number}.")->send();
    }

    private function latestRevisionByFilename(string $filename): ?ConfigurationRevision
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return null;
        }

        return $project->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->first(fn (ConfigurationRevision $revision): bool => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === $filename);
    }

    public function currentProjectHasFtpLogConnection(): bool
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;

        return (bool) $project?->hasFtpLogConnection();
    }

    private function projectQuery()
    {
        return Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()));
    }

    public function getTitle(): string
    {
        return 'Log analyzátor';
    }

    public function getSubheading(): ?string
    {
        return 'Vlož obsah server logu nebo restart.log a najdi problémy, které stojí za pádem nebo chybným chováním serveru.';
    }
}
