<?php

namespace App\Filament\Resources;

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
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Projekty';

    protected static ?string $modelLabel = 'projekt';

    protected static ?string $pluralModelLabel = 'projekty';

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
            Forms\Components\TextInput::make('map')->label('Mapa')->required()->maxLength(255),
            Forms\Components\TextInput::make('game_version')->label('Verze hry')->maxLength(255),
            Forms\Components\Textarea::make('description')->label('Popis')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Název')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('platform')->label('Platforma')->badge()->sortable(),
            Tables\Columns\TextColumn::make('platform_confidence')->label('Jistota')->suffix('%'),
            Tables\Columns\TextColumn::make('map')->label('Mapa')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->label('Upraveno')->dateTime('d. m. Y H:i')->sortable(),
        ])->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
