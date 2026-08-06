<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationRevisionResource\Pages;
use App\Models\ConfigurationRevision;
use App\Services\Dayz\ServerFileLayout;
use App\Services\Ftp\FtpBrowser;
use App\Services\Revision\ConfigurationRevisionEditor;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ConfigurationRevisionResource extends Resource
{
    protected static ?string $model = ConfigurationRevision::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Historie revizí';

    protected static ?string $modelLabel = 'revize';

    protected static ?string $pluralModelLabel = 'revize';

    protected static ?string $navigationGroup = 'Historie a zálohy';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')->label('Server')->relationship(
                name: 'project', titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query): Builder => $query
                    ->when(! auth()->user()?->is_admin, fn (Builder $projectQuery): Builder => $projectQuery->where('user_id', auth()->id())),
            )->disabled(),
            Forms\Components\Select::make('configuration_import_id')
                ->label('Zdrojový import')
                ->relationship('configurationImport', 'original_filename')
                ->disabled(),
            Forms\Components\TextInput::make('revision_number')->label('Číslo revize')->disabled(),
            Forms\Components\TextInput::make('change_summary')->label('Popis změny')->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('sha256')->label('SHA-256')->disabled()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('project.name')->label('Server')->searchable(),
            Tables\Columns\TextColumn::make('configurationImport.original_filename')->label('Soubor')->searchable(),
            Tables\Columns\TextColumn::make('project.platform')
                ->label('Platforma')
                ->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'playstation' => 'PlayStation',
                    'xbox' => 'Xbox',
                    'steam' => 'PC / Steam',
                    default => 'Neznámý',
                })
                ->color(fn (string $state): string => match ($state) {
                    'playstation' => 'info',
                    'xbox' => 'success',
                    'steam' => 'warning',
                    default => 'gray',
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('revision_number')->label('Revize')->prefix('#')->sortable(),
            Tables\Columns\TextColumn::make('change_summary')->label('Změna')->limit(50),
            Tables\Columns\TextColumn::make('creator.name')->label('Autor'),
            Tables\Columns\TextColumn::make('created_at')->label('Vytvořeno')->dateTime('d. m. Y H:i')->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('project_platform')
                ->label('Platforma serveru')
                ->options(['playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam', 'unknown' => 'Neurčeno'])
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->whereHas('project', fn (Builder $project): Builder => $project->where('platform', $data['value']))
                    : $query),
        ])
        ->actions([
            Tables\Actions\Action::make('editor')
                ->label('')
                ->tooltip('Otevřít editor')
                ->icon('heroicon-o-pencil-square')
                ->iconButton()
                ->url(fn (ConfigurationRevision $record): string => url('/admin/projects/'.$record->project_id.'/configuration?revision='.$record->id)),
            Tables\Actions\Action::make('download')
                ->label('')
                ->tooltip('Stáhnout revizi do počítače')
                ->icon('heroicon-o-arrow-down-tray')
                ->iconButton()
                ->action(function (ConfigurationRevision $record) {
                    $record->forceFill(['downloaded_at' => now()])->save();

                    return Storage::disk('dayz')->download(
                        $record->storage_path,
                        app(ConfigurationRevisionEditor::class)->downloadName($record),
                    );
                }),
            Tables\Actions\Action::make('restore')
                ->label('')
                ->tooltip('Vrátit tuto revizi jako novou aktuální revizi')
                ->icon('heroicon-o-arrow-uturn-left')
                ->iconButton()
                ->requiresConfirmation()
                ->modalHeading('Vrátit starší revizi?')
                ->modalDescription('Vytvoří se nová revize z tohoto souboru. Historie zůstane zachována.')
                ->action(function (ConfigurationRevision $record, ConfigurationRevisionEditor $editor): void {
                    $record->loadMissing(['project', 'configurationImport']);
                    $editor->save($record->project, $record, $editor->content($record), 'Obnovena revize #'.$record->revision_number, auth()->user());
                    Notification::make()->success()->title('Revize obnovena')->body('Byla vytvořena nová aktuální revize.')->send();
                }),
            Tables\Actions\Action::make('deploy')
                ->label('')
                ->tooltip('Nahrát tuto revizi na FTP server')
                ->icon('heroicon-o-cloud-arrow-up')
                ->iconButton()
                ->requiresConfirmation()
                ->modalHeading('Nahrát tuto revizi na FTP?')
                ->modalDescription('Tímto se vybraný starší obsah zapíše na server. Doporučujeme nejprve ověřit číslo revize.')
                ->action(function (ConfigurationRevision $record, FtpBrowser $browser, ServerFileLayout $layout): void {
                    $record->loadMissing(['project', 'configurationImport']);
                    $filename = $record->configurationImport?->original_filename ?? basename($record->storage_path);
                    $path = $layout->relativePath($filename, $record->project);
                    $content = Storage::disk('dayz')->get($record->storage_path);
                    $browser->write($record->project, $path, $content);
                    if (hash('sha256', $content) !== hash('sha256', $browser->read($record->project, $path))) {
                        throw new RuntimeException('Server po zápisu vrátil jiný obsah souboru.');
                    }
                    $record->forceFill(['downloaded_at' => now()])->save();
                    Notification::make()->success()->title('Revize nahrána na FTP')->body($filename.' · revize #'.$record->revision_number)->send();
                }),
            Tables\Actions\EditAction::make()->label('')->tooltip('Zobrazit detail')->icon('heroicon-o-eye')->iconButton(),
        ])
        ->emptyStateHeading('Zatím neexistuje žádná revize')
        ->emptyStateDescription('První revize vznikne automaticky při importu konfiguračního souboru.')
        ->emptyStateIcon('heroicon-o-clock');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->when(! auth()->user()?->is_admin, fn (Builder $query) => $query->whereHas('project', fn ($project) => $project->where('user_id', auth()->id())));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListConfigurationRevisions::route('/'), 'edit' => Pages\EditConfigurationRevision::route('/{record}/edit')];
    }
}
