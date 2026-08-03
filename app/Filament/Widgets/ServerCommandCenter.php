<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Services\Dayz\DayzQueryService;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class ServerCommandCenter extends Widget
{
    protected static string $view = 'filament.widgets.server-command-center';

    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedProjectId = null;
    public array $serverStatus = [];

    public function refreshStatus(int $projectId, DayzQueryService $query): void
    {
        $project = Project::query()->when(! auth()->user()?->is_admin, fn (Builder $q): Builder => $q->where('user_id', auth()->id()))->findOrFail($projectId);
        $this->selectedProjectId = $project->id;
        $this->serverStatus = $query->check($project);
    }

    public function getViewData(): array
    {
        $projects = Project::query()
            ->when(! auth()->user()?->is_admin, fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->withCount(['imports', 'revisions'])
            ->latest('updated_at')
            ->get();
        if ($this->selectedProjectId === null && $projects->count() === 1) {
            $this->selectedProjectId = $projects->first()->id;
        }

        return [
            'projectsCount' => $projects->count(),
            'activeProject' => $projects->first(),
            'incompleteCount' => $projects->where('imports_count', 0)->count(),
            'projects' => $projects,
        ];
    }
}
