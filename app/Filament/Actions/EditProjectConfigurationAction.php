<?php

namespace App\Filament\Actions;

use App\Filament\Pages\MapEditor;
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
            ->url(fn (Project $record): string => MapEditor::getUrl(['project' => $record->id]));
    }
}
