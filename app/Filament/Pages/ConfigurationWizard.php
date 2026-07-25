<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ConfigurationWizard extends Page
{
    protected static string $view = 'filament.pages.configuration-wizard';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return 'Průvodce konfigurací';
    }
}
