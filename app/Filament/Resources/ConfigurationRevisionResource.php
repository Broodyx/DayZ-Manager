<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationRevisionResource\Pages;
use App\Models\ConfigurationRevision;
use App\Services\Revision\ConfigurationRevisionEditor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ConfigurationRevisionResource extends Resource
{
    protected static ?string $model = ConfigurationRevision::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Historie revizí';

    protected static ?string $modelLabel = 'revize';

    protected static ?string $pluralModelLabel = 'revize';

    protected static ?string $navigationGroup = 'DayZ konfigurace';

    protected static ?int $navigationSort = 5;

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
        ])->actions([
            Tables\Actions\Action::make('download')
                ->label('Stáhnout')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (ConfigurationRevision $record) => Storage::disk('dayz')->download(
                    $record->storage_path,
                    app(ConfigurationRevisionEditor::class)->downloadName($record),
                )),
            Tables\Actions\EditAction::make()->label('Detail')->icon('heroicon-o-eye'),
        ]);
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
