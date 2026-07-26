<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentProjects extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Naposledy upravené servery';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->when(! auth()->user()?->is_admin, fn (Builder $query): Builder => $query->where('user_id', auth()->id()))
                    ->withCount(['imports', 'revisions'])
                    ->latest('updated_at'),
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Server')
                    ->description(fn (Project $record): string => $record->map),
                Tables\Columns\TextColumn::make('platform')
                    ->label('Platforma')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'playstation' => 'PlayStation',
                        'xbox' => 'Xbox',
                        'steam' => 'PC / Steam',
                        default => 'Neznámá',
                    }),
                Tables\Columns\TextColumn::make('imports_count')->label('Importy')->badge(),
                Tables\Columns\TextColumn::make('revisions_count')->label('Revize')->badge(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Poslední změna')
                    ->since(),
            ])
            ->recordUrl(fn (Project $record): string => ProjectResource::getUrl('configuration', ['record' => $record]))
            ->paginated([5, 10]);
    }
}
