<?php

namespace App\Services\Dayz;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class MapConfigurationReader
{
    public const WORLD_SIZE = 15360;

    /** @return list<array<string, bool|int|float|string|null>> */
    public function markers(string $filename, string $content): array
    {
        $filename = strtolower(basename(str_replace('\\', '/', $filename)));

        if (str_ends_with($filename, '.json')) {
            return $this->jsonMarkers($filename, $content);
        }

        return match (true) {
            $filename === 'cfgplayerspawnpoints.xml' => $this->playerSpawnMarkers($filename, $content),
            $filename === 'cfgeventspawns.xml' => $this->eventSpawnMarkers($filename, $content),
            $filename === 'mapgrouppos.xml' => $this->mapGroupMarkers($filename, $content),
            str_ends_with($filename, '_territories.xml') => $this->territoryMarkers($filename, $content),
            default => [],
        };
    }

    private function playerSpawnMarkers(string $filename, string $content): array
    {
        [$document, $xpath] = $this->xml($content);
        if (! $document || ! $xpath) {
            return [];
        }

        $markers = [];
        foreach ($xpath->query('/playerspawnpoints/*/generator_posbubbles/group/pos') ?: [] as $index => $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $group = $node->parentNode instanceof DOMElement ? $node->parentNode : null;
            $mode = $group?->parentNode?->parentNode;
            $modeName = $mode instanceof DOMElement ? $mode->tagName : 'spawn';
            $widthNode = $xpath->query('/playerspawnpoints/'.$modeName.'/generator_params/grid_width')->item(0);
            $heightNode = $xpath->query('/playerspawnpoints/'.$modeName.'/generator_params/grid_height')->item(0);
            $width = max(1, (float) ($widthNode?->textContent ?: 150));
            $height = max(1, (float) ($heightNode?->textContent ?: 150));
            $x = (float) $node->getAttribute('x');
            $z = (float) $node->getAttribute('z');
            if (! $this->valid($x, $z)) {
                continue;
            }

            $markers[] = $this->marker(
                $filename,
                $x,
                $z,
                ($group?->getAttribute('name') ?: 'Skupina').' · '.$this->spawnModeLabel($modeName),
                'player-spawn-area',
                '/playerspawnpoints/'.$modeName.'/generator_posbubbles/group['.($this->siblingIndex($group) + 1).']/pos['.($this->siblingIndex($node) + 1).']',
                max($width, $height) / 2,
                'Centrum oblasti generátoru. Server uvnitř oblasti hledá vhodný povrch; nejde o přesný spawn bod.',
                true,
                $this->playerSpawnParameters($xpath, $modeName, $group)
            );
        }

        return $markers;
    }

    private function eventSpawnMarkers(string $filename, string $content): array
    {
        [$document, $xpath] = $this->xml($content);
        if (! $document || ! $xpath) {
            return [];
        }

        $markers = [];
        foreach ($xpath->query('/eventposdef/event/pos') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $event = $node->parentNode instanceof DOMElement ? $node->parentNode : null;
            $x = (float) $node->getAttribute('x');
            $z = (float) $node->getAttribute('z');
            if (! $this->valid($x, $z)) {
                continue;
            }
            $markers[] = $this->marker(
                $filename,
                $x,
                $z,
                $event?->getAttribute('name') ?: 'Event',
                'event-spawn',
                '/eventposdef/event['.($this->siblingIndex($event) + 1).']/pos['.($this->siblingIndex($node) + 1).']',
                null,
                'Pevná kandidátní pozice eventu. Natočení: '.($node->getAttribute('a') ?: '0').'°.',
                true,
                ['orientation' => $node->getAttribute('a') ?: '0']
            );
        }

        return $markers;
    }

    /**
     * mapgroupproto.xml defines, per building/group prototype, the relative loot points
     * inside it and which types.xml category/usage tags each point accepts. This reads
     * that mapping so mapgrouppos.xml markers (which only carry the prototype's name) can
     * show what kind of loot the building they represent can actually spawn.
     *
     * @return array<string, list<string>> group name => sorted unique category names
     */
    public function groupPrototypeCategories(string $content): array
    {
        [$document, $xpath] = $this->xml($content);
        if (! $document || ! $xpath) {
            return [];
        }

        $result = [];
        foreach ($xpath->query('//group[@name]') ?: [] as $group) {
            if (! $group instanceof DOMElement) {
                continue;
            }
            $name = $group->getAttribute('name');
            if ($name === '') {
                continue;
            }
            $categories = [];
            foreach ($xpath->query('.//category/@name', $group) ?: [] as $attribute) {
                $value = trim($attribute->nodeValue ?? '');
                if ($value !== '') {
                    $categories[$value] = true;
                }
            }
            if ($categories !== []) {
                $existing = $result[$name] ?? [];
                $result[$name] = array_values(array_unique([...$existing, ...array_keys($categories)]));
                sort($result[$name]);
            }
        }

        return $result;
    }

    private function mapGroupMarkers(string $filename, string $content): array
    {
        [$document, $xpath] = $this->xml($content);
        if (! $document || ! $xpath) {
            return [];
        }

        $markers = [];
        foreach ($xpath->query('/map/group[@pos]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $parts = preg_split('/\s+/', trim($node->getAttribute('pos'))) ?: [];
            if (count($parts) < 3 || ! is_numeric($parts[0]) || ! is_numeric($parts[2])) {
                continue;
            }
            $x = (float) $parts[0];
            $z = (float) $parts[2];
            if (! $this->valid($x, $z)) {
                continue;
            }
            $markers[] = $this->marker(
                $filename,
                $x,
                $z,
                $node->getAttribute('name') ?: 'Map group',
                'map-group',
                '/map/group['.($this->siblingIndex($node) + 1).']',
                null,
                'Umístění mapové skupiny; prostřední hodnota v atributu pos je výška Y.',
                true,
                $this->mapGroupParameters($node, $parts)
            );
        }

        return $markers;
    }

    private function territoryMarkers(string $filename, string $content): array
    {
        [$document, $xpath] = $this->xml($content);
        if (! $document || ! $xpath) {
            return [];
        }

        $markers = [];
        foreach ($xpath->query('//*[@x and @z]') ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $x = (float) $node->getAttribute('x');
            $z = (float) $node->getAttribute('z');
            if (! $this->valid($x, $z)) {
                continue;
            }
            $radius = $node->hasAttribute('r') ? (float) $node->getAttribute('r') : null;
            $markers[] = $this->marker(
                $filename,
                $x,
                $z,
                pathinfo($filename, PATHINFO_FILENAME),
                'territory',
                $node->getNodePath(),
                $radius,
                'Centrum teritoria zvířat'.($radius ? " s poloměrem {$radius} m." : '.'),
                true,
                [
                    'zone_type' => $node->getAttribute('name') ?: 'HuntingGround',
                    'radius' => $node->getAttribute('r') ?: '150',
                    'smin' => $node->getAttribute('smin') ?: '0',
                    'smax' => $node->getAttribute('smax') ?: '0',
                    'dmin' => $node->getAttribute('dmin') ?: '0',
                    'dmax' => $node->getAttribute('dmax') ?: '0',
                ]
            );
        }

        return $markers;
    }

    private function jsonMarkers(string $filename, string $content): array
    {
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return [];
        }

        $markers = [];
        foreach ($this->jsonPositions($decoded) as [$x, $z, $label, $path, $radius]) {
            if ($this->valid($x, $z)) {
                $markers[] = $this->marker($filename, $x, $z, $label ?: $filename, 'json-position', $path, $radius, 'Pozice načtená z JSON konfigurace [X, Y, Z]. Upravte ji ve vizuálním JSON editoru.', false);
            }
        }

        return $markers;
    }

    /** @return list<array{0:float,1:float,2:string,3:string,4:float|null}> */
    private function jsonPositions(array $data, string $label = '', string $path = '$'): array
    {
        $positions = [];
        $currentLabel = (string) ($data['AreaName'] ?? $data['name'] ?? $data['Name'] ?? $label);
        foreach (['Pos', 'pos', 'position', 'Position'] as $key) {
            $value = $data[$key] ?? null;
            if (is_array($value) && count($value) >= 3 && is_numeric($value[0]) && is_numeric($value[2])) {
                $positions[] = [(float) $value[0], (float) $value[2], $currentLabel, $path.'.'.$key, isset($data['Radius']) && is_numeric($data['Radius']) ? (float) $data['Radius'] : null];
            }
        }
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                continue;
            }
            array_push($positions, ...$this->jsonPositions($value, $currentLabel, $path.'.'.$key));
        }

        return $positions;
    }

    /** @return array{0:DOMDocument|null,1:DOMXPath|null} */
    private function xml(string $content): array
    {
        $document = new DOMDocument();
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT)) {
            return [null, null];
        }

        return [$document, new DOMXPath($document)];
    }

    /** @return array<string, bool|int|float|string|null> */
    /** @param array<string, mixed> $parameters */
    private function marker(string $filename, float $x, float $z, string $label, string $type, string $path, ?float $radius, string $help, bool $editable = true, array $parameters = []): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'worldX' => $x,
            'worldZ' => $z,
            'filename' => $filename,
            'path' => $path,
            'radius' => $radius,
            'help' => $help,
            'editable' => $editable,
            'parameters' => $parameters,
        ];
    }

    /** @return array<string, string> */
    private function playerSpawnParameters(DOMXPath $xpath, string $mode, ?DOMElement $group): array
    {
        $parameters = [
            'spawn_mode' => $mode,
            'group_name' => $group?->getAttribute('name') ?? '',
            'group_lifetime_override' => $group?->hasAttribute('lifetime') ? $group->getAttribute('lifetime') : '',
            'group_counter_override' => $group?->hasAttribute('counter') ? $group->getAttribute('counter') : '',
        ];
        foreach ([
            'spawn_params' => [
                'min_dist_infected' => 'min_dist_infected',
                'max_dist_infected' => 'max_dist_infected',
                'min_dist_player' => 'min_dist_player',
                'max_dist_player' => 'max_dist_player',
                'min_dist_static' => 'min_dist_static',
                'max_dist_static' => 'max_dist_static',
            ],
            'generator_params' => [
                'grid_density' => 'grid_density',
                'grid_width' => 'grid_width',
                'grid_height' => 'grid_height',
                'generator_min_dist_static' => 'min_dist_static',
                'generator_max_dist_static' => 'max_dist_static',
                'min_steepness' => 'min_steepness',
                'max_steepness' => 'max_steepness',
            ],
            'group_params' => [
                'enablegroups' => 'enablegroups',
                'groups_as_regular' => 'groups_as_regular',
                'lifetime' => 'lifetime',
                'counter' => 'counter',
            ],
        ] as $section => $values) {
            foreach ($values as $key => $element) {
                $node = $xpath->query('/playerspawnpoints/'.$mode.'/'.$section.'/'.$element)->item(0);
                $parameters[$key] = trim((string) ($node?->textContent ?? ''));
            }
        }

        return $parameters;
    }

    /** @param list<string> $position */
    private function mapGroupParameters(DOMElement $node, array $position): array
    {
        $rotation = preg_split('/\s+/', trim($node->getAttribute('rpy'))) ?: [];

        return [
            'name' => $node->getAttribute('name'),
            'pos_y' => $position[1] ?? '0',
            'pitch' => $rotation[0] ?? '0',
            'yaw' => $rotation[1] ?? '0',
            'roll' => $rotation[2] ?? '0',
            'orientation' => $node->getAttribute('a') ?: '0',
        ];
    }

    private function valid(float $x, float $z): bool
    {
        return $x >= 0 && $z >= 0 && $x <= self::WORLD_SIZE && $z <= self::WORLD_SIZE;
    }

    private function siblingIndex(?DOMElement $node): int
    {
        if (! $node) {
            return 0;
        }
        $index = 0;
        for ($sibling = $node->previousSibling; $sibling; $sibling = $sibling->previousSibling) {
            if ($sibling instanceof DOMElement && $sibling->tagName === $node->tagName) {
                $index++;
            }
        }

        return $index;
    }

    private function spawnModeLabel(string $mode): string
    {
        return match ($mode) {
            'fresh' => 'nová postava',
            'hop' => 'změna serveru',
            'travel' => 'cestovní spawn',
            default => $mode,
        };
    }
}
