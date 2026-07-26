<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    public function getTitle(): string
    {
        return 'Založit DayZ server';
    }

    public function getSubheading(): ?string
    {
        return 'Vytvořte pracovní prostor. Po uložení vás rovnou převezme konfigurační checklist.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Pages\ConfigurationWizard::getUrl(['project' => $this->record->id]);
    }
}
