<?php

namespace App\Filament\Resources\ConfigurationRevisionResource\Pages;

use App\Filament\Resources\ConfigurationRevisionResource;
use Filament\Resources\Pages\ListRecords;

class ListConfigurationRevisions extends ListRecords
{
    protected static string $resource = ConfigurationRevisionResource::class;

    public function getSubheading(): ?string
    {
        return 'Každé uložení vytváří novou obnovitelnou verzi původního konfiguračního souboru.';
    }
}
