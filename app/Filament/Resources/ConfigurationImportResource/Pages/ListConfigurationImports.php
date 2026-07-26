<?php

namespace App\Filament\Resources\ConfigurationImportResource\Pages;

use App\Filament\Resources\ConfigurationImportResource;
use App\Models\Project;
use App\Services\Import\ConfigurationImporter;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;
use Illuminate\Database\Eloquent\Builder;

class ListConfigurationImports extends ListRecords
{
    protected static string $resource = ConfigurationImportResource::class;

    public function booted(): void
    {
        if (request()->boolean('open')) {
            $this->mountAction('import');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Nahrát konfiguraci')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import DayZ konfigurace')
                ->modalDescription('Nahrajte XML, JSON, CFG, TXT nebo ZIP. Soubor se zvaliduje a uloží jako nová revize zvoleného serveru.')
                ->form([
                    Forms\Components\Placeholder::make('workflow_help')
                        ->label('1 · Vyberte oblast nastavení')
                        ->content('Po importu se editor řídí názvem a obsahem souboru. Každá oblast otevře jiný formulář a validaci.'),
                    Forms\Components\Select::make('configuration_area')
                        ->label('Co chcete editovat?')
                        ->options([
                            'server' => 'Server a pravidla · serverDZ.cfg',
                            'economy' => 'Loot a ekonomika · types.xml, globals.xml, economycore.xml',
                            'events' => 'Eventy, vozidla a zvířata · events.xml, cfgeventspawns.xml',
                            'map' => 'Mapa a spawn body · mapgrouppos.xml, cfglplayerspawnpoints.xml',
                            'weather' => 'Počasí · cfgweather.xml',
                            'gameplay' => 'Gameplay · cfggameplay.json',
                            'admin' => 'Administrace · ban.txt, whitelist.txt, messages.xml',
                        ])
                        ->helperText('Volba pomáhá začátečníkům. Samotný formát se ještě ověří podle obsahu souboru.')
                        ->default(fn (): ?string => request()->string('area')->toString() ?: null)
                        ->required(),
                    Forms\Components\Placeholder::make('workflow_server')
                        ->label('2 · Vyberte server')
                        ->content('Konfigurace se uloží do zvoleného serveru jako nová revize.'),
                    Forms\Components\Select::make('project_id')
                        ->label('Server')
                        ->options(fn (): array => Project::query()
                            ->when(! auth()->user()?->is_admin, fn (Builder $query): Builder => $query->where('user_id', auth()->id()))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('server_platform')
                        ->label('Platforma serveru')
                        ->options([
                            'playstation' => 'PlayStation',
                            'xbox' => 'Xbox',
                            'steam' => 'PC / Steam',
                        ])
                        ->helperText('Určuje dostupné možnosti editoru. Obsah souboru se navíc automaticky kontroluje na PC-only prvky.')
                        ->required(),
                    Forms\Components\Placeholder::make('workflow_file')
                        ->label('3 · Nahrajte soubor')
                        ->content('XML, JSON, CFG, TXT nebo ZIP. Po nahrání se vytvoří revize a bezpečnostní validace.'),
                    Forms\Components\FileUpload::make('file')
                        ->label('Soubor konfigurace')
                        ->acceptedFileTypes([
                            'application/xml',
                            'text/xml',
                            'text/plain',
                            'application/json',
                            'application/zip',
                            'application/x-zip-compressed',
                        ])
                        ->helperText('Povolené formáty: XML, JSON, CFG, TXT a ZIP. Maximum odpovídá MAX_UPLOAD_SIZE.')
                        ->maxSize((int) config('dayz.max_upload_size'))
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data, Actions\Action $action): void {
                    try {
                        $project = Project::query()
                            ->when(! auth()->user()?->is_admin, fn (Builder $query): Builder => $query->where('user_id', auth()->id()))
                            ->findOrFail($data['project_id']);
                        $file = $data['file'];

                        if (! $file instanceof TemporaryUploadedFile) {
                            throw new \RuntimeException('Uploaded file is not available.');
                        }

                        $import = app(ConfigurationImporter::class)->import($project, $file, auth()->user());
                        $project->update([
                            'platform' => $data['server_platform'],
                            'platform_confidence' => 100,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Konfigurace byla importována')
                            ->body("{$import->original_filename} · validace: {$import->validation_status}")
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Import se nezdařil')
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
