<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationImportResource\Pages;
use App\Models\ConfigurationImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConfigurationImportResource extends Resource
{
    protected static ?string $model = ConfigurationImport::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('project_id')->relationship(
                name: 'project', titleAttribute: 'name',
                modifyQueryUsing: fn ($query) => $query->where('user_id', auth()->id()),
            )->required(),
            Forms\Components\TextInput::make('original_filename')->required(),
            Forms\Components\TextInput::make('storage_path')->required(),
            Forms\Components\TextInput::make('sha256')->length(64)->required(),
            Forms\Components\TextInput::make('detected_platform')->required(),
            Forms\Components\TextInput::make('detection_confidence')->numeric()->minValue(0)->maxValue(100),
            Forms\Components\Select::make('validation_status')->options([
                'pending' => 'Pending', 'valid' => 'Valid', 'invalid' => 'Invalid',
            ])->required(),
            Forms\Components\DateTimePicker::make('imported_at')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('project.name')->searchable(),
            Tables\Columns\TextColumn::make('original_filename')->searchable(),
            Tables\Columns\TextColumn::make('detected_platform')->badge(),
            Tables\Columns\TextColumn::make('validation_status')->badge(),
            Tables\Columns\TextColumn::make('imported_at')->dateTime()->sortable(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereHas('project', fn ($query) => $query->where('user_id', auth()->id()));
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListConfigurationImports::route('/'), 'edit' => Pages\EditConfigurationImport::route('/{record}/edit')];
    }
}
