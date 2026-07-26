<?php

namespace App\Filament\Resources;

use App\Filament\Actions\EditProjectConfigurationAction;
use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Servery';

    protected static ?string $modelLabel = 'server';

    protected static ?string $pluralModelLabel = 'servery';

    protected static ?string $navigationGroup = 'DayZ konfigurace';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Název')->required()->maxLength(255),
            Forms\Components\Select::make('platform')->label('Platforma')->options([
                'playstation' => 'PlayStation', 'xbox' => 'Xbox',
                'steam' => 'PC / Steam', 'unknown' => 'Neznámá',
            ])->required()->default('unknown'),
            Forms\Components\Select::make('map')->label('Mapa')->options([
                'chernarusplus' => 'Chernarus+',
                'enoch' => 'Livonia (Enoch)',
                'sakhal' => 'Sakhal',
                'namalsk' => 'Namalsk',
                'deerisle' => 'Deerisle',
                'custom' => 'Vlastní mapa',
            ])->searchable()->required(),
            Forms\Components\Select::make('game_version')->label('Verze hry')->options([
                '1.28' => '1.28 (aktuální)', '1.27' => '1.27', '1.26' => '1.26', 'custom' => 'Jiná verze',
            ])->searchable()->native(false),
            Forms\Components\Textarea::make('description')->label('Popis')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Název')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Založil')->searchable()->sortable()->visible(fn (): bool => (bool) auth()->user()?->is_admin),
            Tables\Columns\TextColumn::make('platform')->label('Platforma')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                'playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam', default => 'Neurčeno',
            })->sortable(),
            Tables\Columns\TextColumn::make('platform_confidence')->label('Jistota')->suffix('%')->hiddenFrom('md'),
            Tables\Columns\TextColumn::make('map')->label('Mapa')->searchable()->sortable()->hiddenFrom('md'),
            Tables\Columns\TextColumn::make('updated_at')->label('Upraveno')->dateTime('d. m. Y H:i')->sortable()->hiddenFrom('md'),
        ])
            ->recordUrl(fn (Project $record): string => static::getUrl('configuration', ['record' => $record]))
            ->actions([
                EditProjectConfigurationAction::make(),
                Tables\Actions\EditAction::make()->label('Nastavení serveru'),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->when(! auth()->user()?->is_admin, fn (Builder $query): Builder => $query->where('user_id', auth()->id()));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'configuration' => Pages\EditConfiguration::route('/{record}/configuration'),
        ];
    }
}
