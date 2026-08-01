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
                    Forms\Components\TextInput::make('hosting')
                        ->label('Hosting')
                        ->placeholder('Např. Nitrado')
                        ->datalist(['Nitrado', 'GTX Gaming', 'G-Portal', 'Survival Servers', 'Vlastní server (self-hosted)'])
                        ->helperText('Napiš nebo vyber z nabídky. U Nitrado appka navíc rozpozná jejich specifický export nastavení (dayzps-settings-*.json).')
                        ->maxLength(100),
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
            Forms\Components\Section::make('FTP připojení')
                ->description('Volitelné — appka se pak umí sama připojit na souborový systém serveru a nabídnout prohlížení/import souborů přímo odtamtud. Heslo se ukládá zašifrované, nikdy se nezobrazuje zpět.')
                ->icon('heroicon-o-server-stack')
                ->collapsible()
                ->schema([
                    Forms\Components\Select::make('ftp_protocol')
                        ->label('Protokol')
                        ->options(['ftp' => 'FTP', 'ftps' => 'FTPS (FTP přes SSL)', 'sftp' => 'SFTP (přes SSH)'])
                        ->default('ftp')
                        ->native(false),
                    Forms\Components\TextInput::make('ftp_host')
                        ->label('Hostname')
                        ->placeholder('např. ms2321.gamedata.io')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('ftp_port')
                        ->label('Port')
                        ->numeric()
                        ->placeholder('21 (FTP/FTPS) nebo 22 (SFTP)'),
                    Forms\Components\TextInput::make('ftp_username')
                        ->label('Uživatelské jméno')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('ftp_password')
                        ->label('Heslo')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? $state : null)
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->helperText('Ponech prázdné pro zachování stávajícího hesla.'),
                    Forms\Components\TextInput::make('ftp_root_path')
                        ->label('Kořenová cesta')
                        ->placeholder('např. 1:/dayzps_missions/dayzOffline.chernarusplus/')
                        ->helperText('U Nitrado to bývá ve tvaru "1:/dayzps_missions/<mise>/" — najdeš ji ve FTP údajích hostingu.')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2),
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
                }.($record->hosting ? ' · '.$record->hosting : ''))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name')->label('Založil')->searchable()->sortable()->visible(fn (): bool => (bool) auth()->user()?->is_admin),
            Tables\Columns\TextColumn::make('platform')->label('Platforma')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                'playstation' => 'PlayStation', 'xbox' => 'Xbox', 'steam' => 'PC / Steam', default => 'Neurčeno',
            })->sortable(),
            Tables\Columns\TextColumn::make('imports_count')->label('Soubory')->badge()->color('gray')->sortable(),
            Tables\Columns\TextColumn::make('revisions_count')->label('Revize')->badge()->color('success')->sortable(),
            Tables\Columns\TextColumn::make('undeployed_files_count')
                ->label('Nasazeno na server')
                ->badge()
                ->formatStateUsing(fn (int $state): string => $state > 0 ? "{$state}× nestaženo" : 'Vše staženo')
                ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success')
                ->tooltip('Klikni pro seznam souborů a přímé stažení')
                ->action(
                    Tables\Actions\Action::make('showUndeployedFiles')
                        ->label('Nestažené soubory')
                        ->modalHeading(fn (Project $record): string => 'Nestažené soubory · '.$record->name)
                        ->modalContent(fn (Project $record) => view('filament.undeployed-files-modal', ['project' => $record]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Zavřít'),
                ),
            Tables\Columns\TextColumn::make('updated_at')->label('Poslední změna')->since()->dateTimeTooltip()->sortable(),
        ])
            ->recordUrl(fn (Project $record): string => $record->revisions()->exists()
                ? \App\Filament\Pages\MapEditor::getUrl(['project' => $record->id])
                : static::getUrl('configuration', ['record' => $record]))
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
            ->with(['revisions.configurationImport'])
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
