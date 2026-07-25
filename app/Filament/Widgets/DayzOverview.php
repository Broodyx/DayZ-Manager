<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ConfigurationImportResource;
use App\Filament\Resources\ProjectResource;
use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DayzOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();
        $projects = Project::query()->where('user_id', $userId);
        $imports = ConfigurationImport::query()->whereHas(
            'project',
            fn ($query) => $query->where('user_id', $userId),
        );
        $revisions = ConfigurationRevision::query()->whereHas(
            'project',
            fn ($query) => $query->where('user_id', $userId),
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
