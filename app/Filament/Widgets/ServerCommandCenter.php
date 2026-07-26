<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class ServerCommandCenter extends Widget
{
    protected static string $view = 'filament.widgets.server-command-center';

    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $projects = Project::query()
            ->when(! auth()->user()?->is_admin, fn (Builder $query) => $query->where('user_id', auth()->id()))
            ->withCount(['imports', 'revisions'])
            ->latest('updated_at')
            ->get();

        return [
            'projectsCount' => $projects->count(),
            'activeProject' => $projects->first(),
            'incompleteCount' => $projects->where('imports_count', 0)->count(),
        ];
    }
}
