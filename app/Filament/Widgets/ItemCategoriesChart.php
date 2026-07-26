<?php

namespace App\Filament\Widgets;

use App\Models\ConfigurationRevision;
use App\Services\Revision\TypesXmlEditor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Database\Eloquent\Builder;

class ItemCategoriesChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Položky v ekonomice serverů';

    protected static ?string $description = 'Počty položek z nejnovějších revizí types.xml podle kategorií.';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $counts = [];
        $editor = app(TypesXmlEditor::class);

        $revisions = ConfigurationRevision::query()
            ->when(
                ! auth()->user()?->is_admin,
                fn (Builder $query): Builder => $query->whereHas('project', fn (Builder $project): Builder => $project->where('user_id', auth()->id())),
            )
            ->whereIn('id', function ($query): void {
                $query->selectRaw('MAX(id)')
                    ->from('configuration_revisions')
                    ->whereNotNull('configuration_import_id')
                    ->groupBy('configuration_import_id');
            })
            ->get();

        foreach ($revisions as $revision) {
            try {
                $content = Storage::disk('dayz')->get($revision->storage_path);

                if (! $editor->supports('', $content)) {
                    continue;
                }

                foreach ($editor->entries($content) as $entry) {
                    $category = $this->categoryLabel($entry['category']);
                    $counts[$category] = ($counts[$category] ?? 0) + 1;
                }
            } catch (Throwable) {
                // A missing legacy file must not make the whole dashboard unavailable.
            }
        }

        arsort($counts);

        return [
            'datasets' => [[
                'label' => 'Položky',
                'data' => array_values($counts),
                'backgroundColor' => [
                    '#84cc16', '#65a30d', '#a3e635', '#f59e0b',
                    '#ef4444', '#06b6d4', '#8b5cf6', '#64748b',
                ],
                'borderWidth' => 0,
            ]],
            'labels' => array_keys($counts),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function categoryLabel(string $category): string
    {
        return match (strtolower($category)) {
            'weapons' => 'Zbraně',
            'medical' => 'Léčiva',
            'food' => 'Jídlo a pití',
            'tools' => 'Nástroje',
            'clothes' => 'Oblečení',
            'containers' => 'Kontejnery',
            'vehicles' => 'Vozidla',
            'explosives' => 'Výbušniny',
            default => ucfirst($category ?: 'Ostatní'),
        };
    }
}
