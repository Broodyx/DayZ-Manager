<?php

namespace App\Services\Dayz;

use Illuminate\Support\Facades\Cache;

/**
 * Real inventory-grid dimensions extracted directly from the game's own binarized configs
 * (config.bin — including per-model sub-configs like "AKM\config.bin" — inside every PBO's
 * CfgVehicles / CfgWeapons / CfgMagazines, following name-based class inheritance across the
 * whole scanned file set) — not estimated or guessed. Two distinct, real DayZ properties are
 * tracked per classname, because a class can carry either or both:
 *   footprint — itemSize[]: how much space this classname itself takes up sitting inside
 *               someone else's inventory (almost every lootable item has this).
 *   capacity  — itemsCargoSize[]: the size of the storage grid this classname itself provides
 *               to hold OTHER things (containers, vehicle trunks, and some clothing with
 *               pockets — e.g. a jacket has both its own footprint AND pocket capacity).
 * Classnames absent here simply have no known data (most likely never in the scanned PBO set,
 * e.g. DLC/mod content) — treat that as "unknown", never as zero.
 */
final readonly class CargoSizeCatalog
{
    /** @return array<string, array{classname:string,footprint:array{width:int,height:int}|null,capacity:array{width:int,height:int}|null,source_pbo:string}> keyed by lowercase classname */
    public function all(): array
    {
        return Cache::remember('dayz.catalog.cargo_sizes.v3', now()->addDay(), function (): array {
            $path = base_path('database/seeders/fixtures/dayz-cargo-sizes.json');
            $content = is_file($path) ? file_get_contents($path) : false;
            if ($content === false) {
                return [];
            }

            $entries = json_decode($content, true) ?? [];

            return collect($entries)->mapWithKeys(fn (array $entry) => [
                strtolower($entry['classname']) => [
                    'classname' => $entry['classname'],
                    'footprint' => isset($entry['fw']) ? ['width' => (int) $entry['fw'], 'height' => (int) $entry['fh']] : null,
                    'capacity' => isset($entry['cw']) ? ['width' => (int) $entry['cw'], 'height' => (int) $entry['ch']] : null,
                    'source_pbo' => $entry['source_pbo'],
                ],
            ])->all();
        });
    }

    /** @return array{classname:string,footprint:array{width:int,height:int,slots:int}|null,capacity:array{width:int,height:int,slots:int}|null,source_pbo:string}|null */
    public function lookup(string $classname): ?array
    {
        $entry = $this->all()[strtolower($classname)] ?? null;
        if ($entry === null) {
            return null;
        }

        return [
            'classname' => $entry['classname'],
            'footprint' => $this->withSlots($entry['footprint']),
            'capacity' => $this->withSlots($entry['capacity']),
            'source_pbo' => $entry['source_pbo'],
        ];
    }

    /**
     * Real containers (has its own capacity, not just an item's own footprint) bigger than
     * $minSlots, cheapest-first — used to suggest an alternative when the currently-selected
     * container is about to overflow.
     *
     * @return list<array{classname:string,width:int,height:int,slots:int}>
     */
    public function biggerContainers(int $minSlots, int $limit = 5): array
    {
        return collect($this->all())
            ->filter(fn (array $e) => $e['capacity'] !== null && $e['capacity']['width'] * $e['capacity']['height'] > $minSlots)
            ->map(fn (array $e) => [
                'classname' => $e['classname'],
                'width' => $e['capacity']['width'],
                'height' => $e['capacity']['height'],
                'slots' => $e['capacity']['width'] * $e['capacity']['height'],
            ])
            ->unique('slots')
            ->sortBy('slots')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @param array{width:int,height:int}|null $size */
    private function withSlots(?array $size): ?array
    {
        return $size === null ? null : [...$size, 'slots' => $size['width'] * $size['height']];
    }
}
