<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationImportResource\Pages;
use App\Models\ConfigurationImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ConfigurationImportResource extends Resource
{
    protected static ?string $model = ConfigurationImport::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Historie importů';

    protected static ?string $modelLabel = 'import konfigurace';

    protected static ?string $pluralModelLabel = 'importy konfigurací';

    protected static ?string $navigationGroup = 'Historie a zálohy';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')->label('Server')->relationship(
                name: 'project', titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query): Builder => $query
                    ->when(! auth()->user()?->is_admin, fn (Builder $projectQuery): Builder => $projectQuery->where('user_id', auth()->id())),
            )->disabled(),
            Forms\Components\TextInput::make('original_filename')->label('Původní soubor')->disabled(),
            Forms\Components\TextInput::make('detected_platform')->label('Detekovaná platforma')->disabled(),
            Forms\Components\TextInput::make('detection_confidence')->label('Jistota detekce')->suffix('%')->disabled(),
            Forms\Components\Select::make('validation_status')->label('Validace')->options([
                'pending' => 'Čeká', 'valid' => 'Platná', 'invalid' => 'Neplatná',
            ])->disabled(),
            Forms\Components\DateTimePicker::make('imported_at')->label('Importováno')->disabled(),
            Forms\Components\TextInput::make('sha256')->label('SHA-256')->disabled()->columnSpanFull(),
            Forms\Components\Textarea::make('validation_errors')
                ->label('Chyby validace')
                ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'Bez chyb')
                ->rows(8)
                ->disabled()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('project.name')->label('Server')->searchable(),
            Tables\Columns\TextColumn::make('original_filename')->label('Soubor')->searchable(),
            Tables\Columns\TextColumn::make('detected_platform')->label('Platforma')->badge(),
            Tables\Columns\TextColumn::make('detection_confidence')->label('Jistota')->suffix('%'),
            Tables\Columns\TextColumn::make('validation_status')
                ->label('Validace')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'valid' => 'success',
                    'invalid' => 'danger',
                    default => 'warning',
                }),
            Tables\Columns\TextColumn::make('imported_at')->label('Importováno')->dateTime('d. m. Y H:i')->sortable(),
        ])
        ->defaultSort('imported_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('validation_status')
                ->label('Výsledek validace')
                ->options(['valid' => 'Platná', 'invalid' => 'Neplatná', 'pending' => 'Čeká']),
            Tables\Filters\SelectFilter::make('detected_platform')
                ->label('Detekovaná platforma')
                ->options(['playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam', 'unknown' => 'Neurčeno']),
        ])
        ->actions([
            Tables\Actions\Action::make('download')
                ->label('Stáhnout')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (ConfigurationImport $record) => Storage::disk('dayz')->download(
                    $record->storage_path,
                    $record->original_filename,
                )),
            Tables\Actions\EditAction::make()->label('Detail')->icon('heroicon-o-eye'),
        ])
        ->emptyStateHeading('Zatím nebyla nahrána žádná konfigurace')
        ->emptyStateDescription('Použijte checklist serveru; vybere správnou oblast i očekávaný typ souboru.')
        ->emptyStateIcon('heroicon-o-arrow-up-tray');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->when(! auth()->user()?->is_admin, fn (Builder $query) => $query->whereHas('project', fn ($project) => $project->where('user_id', auth()->id())));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListConfigurationImports::route('/'), 'edit' => Pages\EditConfigurationImport::route('/{record}/edit')];
    }
}
