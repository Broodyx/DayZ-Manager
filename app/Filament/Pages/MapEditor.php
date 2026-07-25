<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MapEditor extends Page
{
    protected static string $view = 'filament.pages.map-editor';

    protected static bool $shouldRegisterNavigation = false;

    public string $map = 'Chernarus';

    public array $markers = [
        ['type' => 'heli', 'label' => 'Heli crash', 'x' => 28, 'y' => 34],
        ['type' => 'convoy', 'label' => 'Konvoj', 'x' => 57, 'y' => 47],
        ['type' => 'spawn', 'label' => 'Loot spawn', 'x' => 72, 'y' => 64],
        ['type' => 'event', 'label' => 'Event spawn', 'x' => 43, 'y' => 75],
    ];

    public function getTitle(): string
    {
        return 'Mapa · '.$this->map;
    }
}
