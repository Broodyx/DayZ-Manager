<?php

namespace App\Services\Dayz;

use Illuminate\Support\Facades\Cache;

/**
 * Real inventory-grid footprints (width x height, in DayZ's own cargo-slot units) extracted
 * directly from the game's binarized configs (config.bin inside each PBO's CfgVehicles /
 * CfgWeapons / CfgMagazines, following name-based class inheritance) — not estimated or
 * guessed. Covers containers (e.g. Barrel_Green = 10x15 = 150 slots) as well as individual
 * items, so both a container's own capacity and what an item inside it costs can be read
 * from the same source. Classnames absent here simply have no known footprint (most likely
 * they were never in the scanned PBO set, e.g. DLC/mod content) — treat that as "unknown",
 * never as zero.
 */
final readonly class CargoSizeCatalog
{
    /** @return array<string, array{width:int,height:int,kind:string,source_pbo:string}> keyed by lowercase classname */
    public function all(): array
    {
        return Cache::remember('dayz.catalog.cargo_sizes.v2', now()->addDay(), function (): array {
            $path = base_path('database/seeders/fixtures/dayz-cargo-sizes.json');
            $content = is_file($path) ? file_get_contents($path) : false;
            if ($content === false) {
                return [];
            }

            $entries = json_decode($content, true) ?? [];

            return collect($entries)->mapWithKeys(fn (array $entry) => [
                strtolower($entry['classname']) => [
                    'classname' => $entry['classname'],
                    'width' => (int) $entry['width'],
                    'height' => (int) $entry['height'],
                    'kind' => $entry['kind'],
                    'source_pbo' => $entry['source_pbo'],
                ],
            ])->all();
        });
    }

    /** @return array{width:int,height:int,slots:int,kind:string,source_pbo:string}|null */
    public function lookup(string $classname): ?array
    {
        $entry = $this->all()[strtolower($classname)] ?? null;
        if ($entry === null) {
            return null;
        }

        return [
            'width' => $entry['width'],
            'height' => $entry['height'],
            'slots' => $entry['width'] * $entry['height'],
            'kind' => $entry['kind'],
            'source_pbo' => $entry['source_pbo'],
        ];
    }

    /**
     * Real containers (own inventory grid, not just an item's own footprint) with a bigger
     * capacity than $minSlots, cheapest-first — used to suggest an alternative when the
     * currently-selected container is about to overflow.
     *
     * @return list<array{classname:string,width:int,height:int,slots:int}>
     */
    public function biggerContainers(int $minSlots, int $limit = 5): array
    {
        return collect($this->all())
            ->filter(fn (array $e) => $e['kind'] === 'container' && $e['width'] * $e['height'] > $minSlots)
            ->map(fn (array $e) => [
                'classname' => $e['classname'],
                'width' => $e['width'],
                'height' => $e['height'],
                'slots' => $e['width'] * $e['height'],
            ])
            ->unique('slots')
            ->sortBy('slots')
            ->take($limit)
            ->values()
            ->all();
    }
}
