<?php

namespace App\Filament\Actions;

use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Tables\Actions\Action;

final class EditProjectConfigurationAction
{
    public static function make(): Action
    {
        return Action::make('editConfiguration')
            ->label('Editor')
            ->icon('heroicon-o-code-bracket')
            ->color('primary')
            ->visible(fn (Project $record): bool => $record->revisions()->exists())
            ->url(fn (Project $record): string => ProjectResource::getUrl('configuration', ['record' => $record]));
    }
}
