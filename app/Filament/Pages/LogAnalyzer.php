<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Dayz\ServerLogAnalyzer;
use Filament\Pages\Page;

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

    public function mount(): void
    {
        $this->projects = $this->projectQuery()->orderBy('name')->pluck('name', 'id')->all();
        $requestedProject = request()->integer('project');
        $this->projectId = array_key_exists($requestedProject, $this->projects)
            ? $requestedProject
            : array_key_first($this->projects);
    }

    public function analyze(ServerLogAnalyzer $analyzer): void
    {
        $this->analyzed = true;
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
    }

    public function clear(): void
    {
        $this->reset(['logContent', 'findings', 'totalLines', 'matchedLines', 'analyzed']);
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
