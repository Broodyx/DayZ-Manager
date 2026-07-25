<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Database\Seeders\DayzDemoSeeder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('demo')
                ->label('Vytvořit demo data')
                ->icon('heroicon-o-beaker')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Vytvořit ukázkové DayZ servery?')
                ->modalDescription('Přidá nebo aktualizuje tři bezpečné demo servery pro váš účet. Vaše existující servery nesmaže.')
                ->action(function (): void {
                    app(DayzDemoSeeder::class)->seedFor(auth()->user());

                    Notification::make()
                        ->success()
                        ->title('Demo data jsou připravena')
                        ->body('Byly vytvořeny servery Chernarus, Livonia a Namalsk.')
                        ->send();
                }),
            Actions\CreateAction::make()->label('Nový server'),
        ];
    }
}
