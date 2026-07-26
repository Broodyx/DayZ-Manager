<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ConfigurationImportResource;
use App\Filament\Resources\ProjectResource;
use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DayzOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $ownOnly = ! auth()->user()?->is_admin;
        $projects = Project::query()->when($ownOnly, fn (Builder $query): Builder => $query->where('user_id', auth()->id()));
        $imports = ConfigurationImport::query()->when(
            $ownOnly,
            fn (Builder $query): Builder => $query->whereHas('project', fn (Builder $project): Builder => $project->where('user_id', auth()->id())),
        );
        $revisions = ConfigurationRevision::query()->when(
            $ownOnly,
            fn (Builder $query): Builder => $query->whereHas('project', fn (Builder $project): Builder => $project->where('user_id', auth()->id())),
        );

        return [
            Stat::make('Servery', (clone $projects)->count())
                ->description('Spravované DayZ servery')
                ->descriptionIcon('heroicon-m-server-stack')
                ->url(ProjectResource::getUrl()),
            Stat::make('Importované soubory', (clone $imports)->count())
                ->description((clone $imports)->where('validation_status', 'valid')->count().' úspěšně ověřeno')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->url(ConfigurationImportResource::getUrl()),
            Stat::make('Uložené revize', (clone $revisions)->count())
                ->description('Historie změn konfigurací')
                ->descriptionIcon('heroicon-m-clock'),
            Stat::make('Platformy', (clone $projects)->distinct()->count('platform'))
                ->description('PlayStation, Xbox nebo PC')
                ->descriptionIcon('heroicon-m-device-phone-mobile'),
        ];
    }
}
