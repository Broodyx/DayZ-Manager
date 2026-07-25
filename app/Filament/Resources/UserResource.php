<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Uživatelé';

    protected static ?string $modelLabel = 'uživatel';

    protected static ?string $pluralModelLabel = 'uživatelé';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administrace';

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Jméno')->required(),
            Forms\Components\TextInput::make('email')->label('E-mail')->email()->required(),
            Forms\Components\TextInput::make('password')->label('Heslo')->password()->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)->dehydrated(fn ($state) => filled($state))->required(fn (string $operation): bool => $operation === 'create'),
            Forms\Components\Toggle::make('is_admin')->label('Administrátor'),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Jméno')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('E-mail')->searchable(),
            Tables\Columns\IconColumn::make('is_admin')->label('Admin')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->label('Vytvořen')->dateTime(),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/'), 'create' => Pages\CreateUser::route('/create'), 'edit' => Pages\EditUser::route('/{record}/edit')];
    }
}
