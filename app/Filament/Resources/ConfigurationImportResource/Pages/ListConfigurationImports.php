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

class ListConfigurationImports extends ListRecords
{
    protected static string $resource = ConfigurationImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Nahrát konfiguraci')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->modalHeading('Import DayZ konfigurace')
                ->modalDescription('Nahrajte XML, JSON nebo ZIP. Soubor se bezpečně uloží, zvaliduje a vytvoří novou revizi projektu.')
                ->form([
                    Forms\Components\Select::make('project_id')
                        ->label('Server')
                        ->options(fn (): array => Project::query()
                            ->where('user_id', auth()->id())
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('server_platform')
                        ->label('Typ serveru')
                        ->options([
                            'playstation' => 'PlayStation',
                            'xbox' => 'Xbox',
                            'steam' => 'PC / Steam',
                        ])
                        ->helperText('Určuje dostupné možnosti editoru. Obsah souboru se navíc automaticky kontroluje na PC-only prvky.')
                        ->required(),
                    Forms\Components\FileUpload::make('file')
                        ->label('Konfigurační soubor')
                        ->acceptedFileTypes([
                            'application/xml',
                            'text/xml',
                            'text/plain',
                            'application/json',
                            'application/zip',
                            'application/x-zip-compressed',
                        ])
                        ->helperText('Povolené formáty: XML, JSON a ZIP. Maximum odpovídá MAX_UPLOAD_SIZE.')
                        ->maxSize((int) config('dayz.max_upload_size'))
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data, Actions\Action $action): void {
                    try {
                        $project = Project::query()
                            ->where('user_id', auth()->id())
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
