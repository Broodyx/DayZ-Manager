<?php

namespace App\Filament\Resources\ConfigurationImportResource\Pages;

use App\Filament\Resources\ConfigurationImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConfigurationImports extends ListRecords
{
    protected static string $resource = ConfigurationImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('wizard')
                ->label('Přidat konfigurační soubor')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->url('/admin/configuration-wizard'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Audit původních uploadů. Pro úpravy používejte konkrétní revizi v editoru.';
    }
}
