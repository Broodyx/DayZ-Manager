<?php

namespace App\Filament\Pages;

use App\Services\Dayz\ConfigurationCatalog;
use Filament\Pages\Page;

class ConfigurationWizard extends Page
{
    protected static string $view = 'filament.pages.configuration-wizard';

    protected static bool $shouldRegisterNavigation = false;

    public array $areas = [];

    public function mount(ConfigurationCatalog $catalog): void
    {
        $this->areas = $catalog->areas();
    }

    public function getTitle(): string
    {
        return 'Průvodce konfigurací';
    }
}
