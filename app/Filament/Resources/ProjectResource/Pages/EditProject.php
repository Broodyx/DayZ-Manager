<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public function getTitle(): string
    {
        return 'Nastavení serveru';
    }

    public function getSubheading(): ?string
    {
        return 'Základní identita serveru, platforma a mapa. Konfigurační soubory upravujte v editoru.';
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
