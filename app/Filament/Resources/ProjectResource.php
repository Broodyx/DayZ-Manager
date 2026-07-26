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
            Forms\Components\Section::make('Identita serveru')
                ->description('Podle platformy a mapy průvodce nabídne jen relevantní konfigurace a kontroly kompatibility.')
                ->icon('heroicon-o-server-stack')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Název serveru')
                        ->placeholder('Např. Nitrado – Chernarus Hardcore')
                        ->helperText('Interní název v DayZ Manageru. Nemusí být shodný s hostname serveru.')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('platform')
                        ->label('Platforma')
                        ->options([
                            'playstation' => 'PlayStation',
                            'xbox' => 'Xbox',
                            'steam' => 'PC / Steam',
                            'unknown' => 'Zatím neurčeno',
                        ])
                        ->helperText('Určuje dostupné soubory, modifikace a upozornění na PC-only nastavení.')
                        ->required()
                        ->native(false)
                        ->default('unknown'),
                    Forms\Components\Select::make('map')
                        ->label('Mapa')
                        ->options([
                            'chernarusplus' => 'Chernarus+',
                            'enoch' => 'Livonia (Enoch)',
                            'sakhal' => 'Sakhal',
                            'namalsk' => 'Namalsk',
                            'deerisle' => 'Deerisle',
                            'custom' => 'Vlastní mapa',
                        ])
                        ->helperText('Mapový editor je nyní plně kalibrovaný pro Chernarus+; ostatní mapy zůstanou dostupné v konfiguraci.')
                        ->searchable()
                        ->native(false)
                        ->required(),
                    Forms\Components\Select::make('game_version')
                        ->label('Verze DayZ')
                        ->options([
                            '1.28' => '1.28',
                            '1.27' => '1.27',
                            '1.26' => '1.26',
                            'custom' => 'Jiná / vlastní',
                        ])
                        ->helperText('Používá se při porovnání konfigurace s katalogem podporované verze.')
                        ->searchable()
                        ->native(false),
                ])
                ->columns(2),
            Forms\Components\Section::make('Poznámka pro administrátory')
                ->description('Volitelný kontext, podle kterého server později bezpečně poznáte.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Popis')
                        ->placeholder('Hosting, účel serveru, kontakt na správce nebo plánované úpravy…')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Server')
                ->description(fn (Project $record): string => match ($record->map) {
                    'chernarusplus' => 'Chernarus+',
                    'enoch' => 'Livonia',
                    'sakhal' => 'Sakhal',
                    default => ucfirst($record->map ?: 'Mapa neurčena'),
                }.($record->game_version ? ' · DayZ '.$record->game_version : ''))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Založil')->searchable()->sortable()->visible(fn (): bool => (bool) auth()->user()?->is_admin),
            Tables\Columns\TextColumn::make('platform')->label('Platforma')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                'playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam', default => 'Neurčeno',
            })->sortable(),
            Tables\Columns\TextColumn::make('imports_count')->label('Soubory')->badge()->color('gray')->sortable(),
            Tables\Columns\TextColumn::make('revisions_count')->label('Revize')->badge()->color('success')->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->label('Poslední změna')->since()->dateTimeTooltip()->sortable(),
        ])
            ->recordUrl(fn (Project $record): string => static::getUrl('configuration', ['record' => $record]))
            ->filters([
                Tables\Filters\SelectFilter::make('platform')->label('Platforma')->options([
                    'playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam', 'unknown' => 'Neurčeno',
                ]),
                Tables\Filters\SelectFilter::make('map')->label('Mapa')->options([
                    'chernarusplus' => 'Chernarus+', 'enoch' => 'Livonia', 'sakhal' => 'Sakhal', 'namalsk' => 'Namalsk',
                ]),
            ])
            ->actions([
                EditProjectConfigurationAction::make()->label('Otevřít'),
                Tables\Actions\EditAction::make()->label('Nastavení'),
            ])
            ->emptyStateHeading('Zatím nemáte žádný server')
            ->emptyStateDescription('Založte server a průvodce vás provede výběrem a importem konfigurace.')
            ->emptyStateIcon('heroicon-o-server-stack')
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['imports', 'revisions'])
            ->when(! auth()->user()?->is_admin, fn (Builder $query): Builder => $query->where('user_id', auth()->id()));
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
