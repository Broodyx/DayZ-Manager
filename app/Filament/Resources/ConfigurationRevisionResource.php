<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationRevisionResource\Pages;
use App\Models\ConfigurationRevision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConfigurationRevisionResource extends Resource
{
    protected static ?string $model = ConfigurationRevision::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')->relationship(
                name: 'project', titleAttribute: 'name',
                modifyQueryUsing: fn ($query) => $query->where('user_id', auth()->id()),
            )->required(),
            Forms\Components\Select::make('configuration_import_id')->relationship('configurationImport', 'original_filename'),
            Forms\Components\TextInput::make('revision_number')->numeric()->required(),
            Forms\Components\TextInput::make('storage_path')->required(),
            Forms\Components\TextInput::make('sha256')->length(64)->required(),
            Forms\Components\Textarea::make('change_summary')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('project.name')->searchable(),
            Tables\Columns\TextColumn::make('revision_number')->sortable(),
            Tables\Columns\TextColumn::make('change_summary')->limit(50),
            Tables\Columns\TextColumn::make('creator.name'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereHas('project', fn ($query) => $query->where('user_id', auth()->id()));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListConfigurationRevisions::route('/'), 'edit' => Pages\EditConfigurationRevision::route('/{record}/edit')];
    }
}
