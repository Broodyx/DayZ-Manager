<?php

namespace App\Services\Dayz;

use Illuminate\Support\Facades\Cache;

/**
 * Real placeable DayZ classes (buildings, static props, containers, vehicles...) extracted
 * directly from the game's own binarized configs — see tools/DayzMapTiler's `object-catalog`
 * subcommand for how database/seeders/fixtures/dayz-object-catalog.json was generated. Same
 * storage pattern as ClassnameCatalog/CargoSizeCatalog: a static fixture file, lazily loaded and
 * cached, no database table — this data never changes without regenerating the fixture from PBOs.
 *
 * `category` is NOT a real DayZ config property (the engine has no "placement category" field)
 * — it was inferred from classname prefixes and inheritance during extraction, so treat it as a
 * best-effort grouping, not an authoritative taxonomy. `display_name` is often null: DayZ configs
 * almost always reference display text through a "$STR_..." stringtable key rather than a plain
 * string, and no stringtable parser exists here, so any raw "$STR_..." value is discarded at
 * fixture-generation time rather than shown as if it were readable. Classname is what's shown to
 * the user until a stringtable resolver exists.
 */
final readonly class ObjectCatalogService
{
    public function __construct(private CargoSizeCatalog $cargoSizeCatalog) {}

    /** @return array<string, array{classname:string,display_name:?string,category:string,model:?string,parent_class:string,source_pbo:string,tags:list<string>,can_spawn:bool,can_persist:bool,has_inventory:bool,inventory_slots:?int,width:?int,height:?int,supports_attachments:bool,bounding_box:null,preview_image:null}> keyed by lowercase classname */
    public function all(): array
    {
        return Cache::remember('dayz.catalog.objects.v1', now()->addDay(), function (): array {
            $path = base_path('database/seeders/fixtures/dayz-object-catalog.json');
            $content = is_file($path) ? file_get_contents($path) : false;
            if ($content === false) {
                return [];
            }

            $entries = json_decode($content, true) ?? [];

            return collect($entries)->mapWithKeys(function (array $entry): array {
                $cargo = $this->cargoSizeCatalog->lookup($entry['classname']);
                $capacity = $cargo['capacity'] ?? null;

                return [strtolower($entry['classname']) => [
                    'classname' => $entry['classname'],
                    'display_name' => $this->readableDisplayName($entry['display_name'] ?? null),
                    'category' => $entry['category'],
                    'model' => $entry['model'] ?? null,
                    'parent_class' => $entry['parent_class'] ?? '',
                    'source_pbo' => $entry['source_pbo'],
                    'tags' => $entry['tags'] ?? [],
                    // Universally true for everything in this catalog by construction (it only
                    // contains classes the Object Spawner extraction pass judged placeable), not
                    // a per-class distinction the source config actually makes.
                    'can_spawn' => true,
                    'can_persist' => true,
                    'has_inventory' => $capacity !== null,
                    'inventory_slots' => $capacity['slots'] ?? null,
                    'width' => $capacity['width'] ?? null,
                    'height' => $capacity['height'] ?? null,
                    'supports_attachments' => false,
                    // Needs a .p3d model parser (not implemented anywhere in this project yet) —
                    // config.bin alone never carries model geometry, only the path to it.
                    'bounding_box' => null,
                    'preview_image' => null,
                ]];
            })->all();
        });
    }

    /** @return array{classname:string,display_name:?string,category:string,model:?string,parent_class:string,source_pbo:string,tags:list<string>,can_spawn:bool,can_persist:bool,has_inventory:bool,inventory_slots:?int,width:?int,height:?int,supports_attachments:bool,bounding_box:null,preview_image:null}|null */
    public function find(string $classname): ?array
    {
        return $this->all()[strtolower($classname)] ?? null;
    }

    /** @return list<string> distinct categories, alphabetically sorted */
    public function categories(): array
    {
        return collect($this->all())->pluck('category')->unique()->sort()->values()->all();
    }

    /**
     * Case-insensitive substring match across classname/display_name/tags/category — fast even
     * over a few thousand entries in plain PHP, so no dedicated fulltext index is needed for a
     * catalog this size.
     *
     * @return list<array{classname:string,display_name:?string,category:string,model:?string,parent_class:string,source_pbo:string,tags:list<string>,can_spawn:bool,can_persist:bool,has_inventory:bool,inventory_slots:?int,width:?int,height:?int,supports_attachments:bool,bounding_box:null,preview_image:null}>
     */
    public function search(string $query, ?string $category = null, int $limit = 300): array
    {
        $needle = strtolower(trim($query));

        return collect($this->all())
            ->when($category !== null && $category !== '', fn ($items) => $items->where('category', $category))
            ->filter(function (array $entry) use ($needle): bool {
                if ($needle === '') {
                    return true;
                }

                $haystacks = [
                    strtolower($entry['classname']),
                    strtolower((string) $entry['display_name']),
                    strtolower($entry['category']),
                    ...array_map(strtolower(...), $entry['tags']),
                ];

                foreach ($haystacks as $haystack) {
                    if (str_contains($haystack, $needle)) {
                        return true;
                    }
                }

                return false;
            })
            ->sortBy('classname')
            ->take($limit)
            ->values()
            ->all();
    }

    private function readableDisplayName(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Bohemia's convention for an unresolved stringtable key ("$STR_...") — showing this
        // verbatim would be worse than showing nothing, since it looks like a real name.
        return str_starts_with($raw, '$') ? null : $raw;
    }
}
