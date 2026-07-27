<?php

namespace App\Services\Dayz;

use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;
use RuntimeException;

final class MapConfigurationEditor
{
    /** @return array{content:string,deleted:int} */
    public function deleteScope(string $filename, string $content, string $scope): array
    {
        return $this->deleteScopes($filename, $content, [$scope]);
    }

    /**
     * Deletes every node matched by any of the given scopes in a single pass, producing one revision.
     *
     * @param  list<string>  $scopes
     * @return array{content:string,deleted:int}
     */
    public function deleteScopes(string $filename, string $content, array $scopes): array
    {
        $filename = strtolower(basename(str_replace('\\', '/', $filename)));
        $document = $this->document($content);
        $xpath = new DOMXPath($document);
        $nodes = [];
        foreach ($scopes as $scope) {
            foreach ($this->resolveScopeNodes($xpath, $filename, $scope) as $node) {
                $nodes[spl_object_id($node)] = $node;
            }
        }

        $deleted = 0;
        $parentsToPrune = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement && $node->parentNode) {
                $parent = $node->parentNode;
                if ($parent instanceof DOMElement && in_array($parent->tagName, ['group', 'event'], true)) {
                    $parentsToPrune[spl_object_id($parent)] = $parent;
                }
                $parent->removeChild($node);
                $deleted++;
            }
        }
        if ($deleted === 0) {
            throw new RuntimeException('Ve vybraných skupinách nebyly nalezeny žádné body.');
        }
        // A group/event with no remaining <pos> children is an orphaned, non-functional entry, so drop it too.
        foreach ($parentsToPrune as $parent) {
            if ($parent->parentNode && $xpath->query('./pos', $parent)->length === 0) {
                $parent->parentNode->removeChild($parent);
            }
        }

        return ['content' => $this->save($document), 'deleted' => $deleted];
    }

    /** @return list<DOMElement> */
    private function resolveScopeNodes(DOMXPath $xpath, string $filename, string $scope): array
    {
        if ($filename === 'cfgeventspawns.xml') {
            if ($scope === 'all') {
                return iterator_to_array($xpath->query('/eventposdef/event/pos') ?: []);
            }
            if (str_starts_with($scope, 'event:')) {
                $eventName = substr($scope, 6);
                foreach ($xpath->query('/eventposdef/event') ?: [] as $event) {
                    if ($event instanceof DOMElement && hash_equals($event->getAttribute('name'), $eventName)) {
                        return iterator_to_array($xpath->query('./pos', $event) ?: []);
                    }
                }
            }

            return [];
        }

        if ($filename === 'cfgplayerspawnpoints.xml') {
            if ($scope === 'all') {
                return iterator_to_array($xpath->query('/playerspawnpoints/*/generator_posbubbles/group/pos') ?: []);
            }
            if (preg_match('/^mode:(fresh|hop|travel)$/', $scope, $matches)) {
                return iterator_to_array($xpath->query('/playerspawnpoints/'.$matches[1].'/generator_posbubbles/group/pos') ?: []);
            }
            if (preg_match('/^group:(fresh|hop|travel)\|(.+)$/', $scope, $matches)) {
                foreach ($xpath->query('/playerspawnpoints/'.$matches[1].'/generator_posbubbles/group') ?: [] as $group) {
                    if ($group instanceof DOMElement && hash_equals($group->getAttribute('name'), $matches[2])) {
                        return iterator_to_array($xpath->query('./pos', $group) ?: []);
                    }
                }
            }

            return [];
        }

        throw new RuntimeException('Hromadné mazání pro tento mapový soubor není podporováno.');
    }

    /** @param array<string, mixed> $parameters */
    public function updateCoordinates(string $filename, string $content, string $path, float $x, float $z, array $parameters = []): string
    {
        $this->validateCoordinates($x, $z);
        [$document, $node] = $this->node($content, $path);

        if ($node->hasAttribute('x') && $node->hasAttribute('z')) {
            $node->setAttribute('x', $this->number($x));
            $node->setAttribute('z', $this->number($z));
            $normalizedFilename = strtolower(basename($filename));
            if ($normalizedFilename === 'cfgplayerspawnpoints.xml') {
                $this->updatePlayerSpawnConfiguration($document, $node, $parameters);
            } elseif ($normalizedFilename === 'cfgeventspawns.xml' && array_key_exists('orientation', $parameters)) {
                $orientation = (float) $parameters['orientation'];
                if ($orientation < 0 || $orientation >= 360) {
                    throw new RuntimeException('Natočení musí být v rozsahu 0 až méně než 360°.');
                }
                $node->setAttribute('a', $this->number($orientation));
            } elseif (str_ends_with($normalizedFilename, '_territories.xml')) {
                $radius = (float) ($parameters['radius'] ?? $node->getAttribute('r'));
                if ($radius < 1 || $radius > 5000) {
                    throw new RuntimeException('Poloměr teritoria musí být v rozsahu 1–5000 metrů.');
                }
                foreach (['smin', 'smax', 'dmin', 'dmax'] as $attribute) {
                    if (array_key_exists($attribute, $parameters)) {
                        $node->setAttribute($attribute, (string) max(0, (int) $parameters[$attribute]));
                    }
                }
                if (($parameters['zone_type'] ?? '') !== '') {
                    $node->setAttribute('name', (string) $parameters['zone_type']);
                }
                $node->setAttribute('r', $this->number($radius));
            }
        } elseif (strtolower(basename($filename)) === 'mapgrouppos.xml' && $node->hasAttribute('pos')) {
            $parts = preg_split('/\s+/', trim($node->getAttribute('pos'))) ?: [];
            if (count($parts) < 3) {
                throw new RuntimeException('Atribut pos nemá očekávaný formát X Y Z.');
            }
            $parts[0] = $this->number($x);
            $parts[2] = $this->number($z);
            $node->setAttribute('pos', implode(' ', $parts));
            if (array_key_exists('pos_y', $parameters)) {
                $parts[1] = $this->number((float) $parameters['pos_y']);
                $node->setAttribute('pos', implode(' ', $parts));
            }
            if (array_intersect(['pitch', 'yaw', 'roll'], array_keys($parameters))) {
                $node->setAttribute('rpy', implode(' ', array_map(fn ($value) => $this->number((float) $value), [
                    $parameters['pitch'] ?? 0, $parameters['yaw'] ?? 0, $parameters['roll'] ?? 0,
                ])));
            }
            if (array_key_exists('orientation', $parameters)) {
                $node->setAttribute('a', $this->number((float) $parameters['orientation']));
            }
            $name = trim((string) ($parameters['name'] ?? ''));
            if ($name !== '') {
                if (! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
                    throw new RuntimeException('Classname smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.');
                }
                $node->setAttribute('name', $name);
            }
        } else {
            throw new RuntimeException('Tento bod nemá editovatelné světové souřadnice X/Z.');
        }

        return $this->save($document);
    }

    public function delete(string $content, string $path): string
    {
        [$document, $node] = $this->node($content, $path);
        if (! $node->parentNode) {
            throw new RuntimeException('Kořenový element nelze odstranit.');
        }
        $node->parentNode->removeChild($node);

        return $this->save($document);
    }

    /** @param array<string, mixed> $parameters */
    public function appendPlayerSpawnArea(string $content, string $groupName, float $x, float $z, string $mode = 'fresh', array $parameters = []): string
    {
        $this->validateCoordinates($x, $z);
        if (! in_array($mode, ['fresh', 'hop', 'travel'], true)) {
            throw new RuntimeException('Režim spawnu musí být fresh, hop nebo travel.');
        }
        $document = $this->document($content);
        $xpath = new DOMXPath($document);
        $container = $xpath->query('/playerspawnpoints/'.$mode.'/generator_posbubbles')->item(0);
        if (! $container instanceof DOMElement) {
            throw new RuntimeException("V cfgplayerspawnpoints.xml chybí {$mode}/generator_posbubbles.");
        }
        $group = null;
        foreach ($xpath->query('/playerspawnpoints/'.$mode.'/generator_posbubbles/group') ?: [] as $candidate) {
            if ($candidate instanceof DOMElement && $candidate->getAttribute('name') === $groupName) {
                $group = $candidate;
                break;
            }
        }
        if (! $group) {
            $group = $document->createElement('group');
            $group->setAttribute('name', $groupName);
            $container->appendChild($group);
        }
        $this->setOptionalIntegerAttribute($group, 'lifetime', $parameters['group_lifetime_override'] ?? null);
        $this->setOptionalIntegerAttribute($group, 'counter', $parameters['group_counter_override'] ?? null);
        $position = $document->createElement('pos');
        $position->setAttribute('x', $this->number($x));
        $position->setAttribute('z', $this->number($z));
        $group->appendChild($position);
        $this->updatePlayerModeConfiguration($document, $mode, $parameters);

        return $this->save($document);
    }

    /** @param array<string, mixed> $parameters */
    private function updatePlayerSpawnConfiguration(DOMDocument $document, DOMElement $position, array $parameters): void
    {
        $group = $position->parentNode;
        $container = $group?->parentNode;
        $mode = $container?->parentNode;
        if (! $group instanceof DOMElement || ! $mode instanceof DOMElement || ! in_array($mode->tagName, ['fresh', 'hop', 'travel'], true)) {
            throw new RuntimeException('Bod neleží v podporované sekci fresh, hop nebo travel.');
        }

        $groupName = trim((string) ($parameters['group_name'] ?? ''));
        if ($groupName !== '') {
            $group->setAttribute('name', $groupName);
        }
        $this->setOptionalIntegerAttribute($group, 'lifetime', $parameters['group_lifetime_override'] ?? null);
        $this->setOptionalIntegerAttribute($group, 'counter', $parameters['group_counter_override'] ?? null);
        $this->updatePlayerModeConfiguration($document, $mode->tagName, $parameters);
    }

    /** @param array<string, mixed> $parameters */
    private function updatePlayerModeConfiguration(DOMDocument $document, string $mode, array $parameters): void
    {
        $xpath = new DOMXPath($document);
        $definitions = [
            'spawn_params' => [
                'min_dist_infected', 'max_dist_infected', 'min_dist_player',
                'max_dist_player', 'min_dist_static', 'max_dist_static',
            ],
            'generator_params' => [
                'grid_density', 'grid_width', 'grid_height', 'generator_min_dist_static',
                'generator_max_dist_static', 'min_steepness', 'max_steepness',
            ],
            'group_params' => ['enablegroups', 'groups_as_regular', 'lifetime', 'counter'],
        ];
        $xmlNames = [
            'generator_min_dist_static' => 'min_dist_static',
            'generator_max_dist_static' => 'max_dist_static',
            'enablegroups' => 'enablegroups',
            'groups_as_regular' => 'groups_as_regular',
            'lifetime' => 'lifetime',
            'counter' => 'counter',
        ];

        foreach ($definitions as $section => $keys) {
            $sectionNode = $xpath->query('/playerspawnpoints/'.$mode.'/'.$section)->item(0);
            if (! $sectionNode instanceof DOMElement) {
                $modeNode = $xpath->query('/playerspawnpoints/'.$mode)->item(0);
                if (! $modeNode instanceof DOMElement) {
                    throw new RuntimeException("V cfgplayerspawnpoints.xml chybí sekce {$mode}.");
                }
                $sectionNode = $document->createElement($section);
                $modeNode->insertBefore($sectionNode, $modeNode->firstChild);
            }
            foreach ($keys as $key) {
                if (! array_key_exists($key, $parameters) || $parameters[$key] === '') {
                    continue;
                }
                $xmlName = $xmlNames[$key] ?? $key;
                $value = $parameters[$key];
                if (in_array($key, ['enablegroups', 'groups_as_regular'], true)) {
                    $value = $this->boolean($value);
                } else {
                    $value = $this->validatedPlayerNumber($key, $value);
                }
                $node = $xpath->query('./'.$xmlName, $sectionNode)->item(0);
                if (! $node instanceof DOMElement) {
                    $node = $document->createElement($xmlName);
                    $sectionNode->appendChild($node);
                }
                $node->nodeValue = (string) $value;
            }
        }

        $this->validatePlayerPairs($parameters);
    }

    private function validatedPlayerNumber(string $key, mixed $value): string
    {
        if (! is_numeric($value)) {
            throw new RuntimeException("Parametr {$key} musí být číslo.");
        }
        $number = (float) $value;
        if (in_array($key, ['lifetime', 'counter'], true) && ($number < -1 || floor($number) !== $number)) {
            throw new RuntimeException("Parametr {$key} musí být celé číslo -1 nebo vyšší.");
        }
        if ($key === 'grid_density' && ($number < 1 || $number > 1000 || floor($number) !== $number)) {
            throw new RuntimeException('Hustota mřížky musí být celé číslo 1–1000.');
        }
        if (in_array($key, ['grid_width', 'grid_height'], true) && ($number < 1 || $number > MapConfigurationReader::WORLD_SIZE)) {
            throw new RuntimeException("Parametr {$key} musí být v rozsahu 1–15360 metrů.");
        }
        if (in_array($key, ['min_steepness', 'max_steepness'], true) && ($number < -90 || $number > 90)) {
            throw new RuntimeException("Parametr {$key} musí být v rozsahu -90 až 90 stupňů.");
        }
        if (str_contains($key, 'dist_') && ($number < 0 || $number > MapConfigurationReader::WORLD_SIZE)) {
            throw new RuntimeException("Parametr {$key} musí být v rozsahu 0–15360 metrů.");
        }

        return $this->number($number);
    }

    /** @param array<string, mixed> $parameters */
    private function validatePlayerPairs(array $parameters): void
    {
        foreach ([
            ['min_dist_infected', 'max_dist_infected'],
            ['min_dist_player', 'max_dist_player'],
            ['min_dist_static', 'max_dist_static'],
            ['generator_min_dist_static', 'generator_max_dist_static'],
            ['min_steepness', 'max_steepness'],
        ] as [$minimum, $maximum]) {
            if (isset($parameters[$minimum], $parameters[$maximum])
                && $parameters[$minimum] !== '' && $parameters[$maximum] !== ''
                && (float) $parameters[$minimum] > (float) $parameters[$maximum]) {
                throw new RuntimeException("Hodnota {$minimum} nesmí být vyšší než {$maximum}.");
            }
        }
    }

    private function boolean(mixed $value): string
    {
        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes', 'on' => 'true',
            '0', 'false', 'no', 'off' => 'false',
            default => throw new RuntimeException('Logická hodnota musí být true nebo false.'),
        };
    }

    private function setOptionalIntegerAttribute(DOMElement $element, string $name, mixed $value): void
    {
        if ($value === null || $value === '') {
            $element->removeAttribute($name);
            return;
        }
        if (! is_numeric($value) || (float) $value < -1 || floor((float) $value) !== (float) $value) {
            throw new RuntimeException("Atribut {$name} musí být celé číslo -1 nebo vyšší.");
        }
        $element->setAttribute($name, (string) (int) $value);
    }

    /** @param array<string, mixed>|float|int $parameters */
    public function appendTerritoryZone(string $content, string $zoneName, float $x, float $z, array|float|int $parameters = []): string
    {
        $this->validateCoordinates($x, $z);
        $parameters = is_array($parameters) ? $parameters : ['radius' => $parameters];
        $radius = (float) ($parameters['radius'] ?? 150);
        if ($radius < 1 || $radius > 5000) {
            throw new RuntimeException('Poloměr teritoria musí být v rozsahu 1–5000 metrů.');
        }
        $document = $this->document($content);
        $root = $document->documentElement;
        if (! $root || $root->tagName !== 'territory-type') {
            throw new RuntimeException('Očekáván je kořenový element territory-type.');
        }
        $territory = $document->createElement('territory');
        $territory->setAttribute('color', '4291611852');
        $zone = $document->createElement('zone');
        foreach ([
            'name' => $zoneName ?: 'HuntingGround',
            'smin' => (string) ($parameters['smin'] ?? 0),
            'smax' => (string) ($parameters['smax'] ?? 0),
            'dmin' => (string) ($parameters['dmin'] ?? 0),
            'dmax' => (string) ($parameters['dmax'] ?? 0),
        ] as $name => $value) {
            $zone->setAttribute($name, $value);
        }
        $zone->setAttribute('x', $this->number($x));
        $zone->setAttribute('z', $this->number($z));
        $zone->setAttribute('r', $this->number($radius));
        $territory->appendChild($zone);
        $root->appendChild($territory);

        return $this->save($document);
    }

    /** @param array<string, mixed> $parameters */
    public function appendMapGroup(string $content, string $groupName, float $x, float $z, array $parameters = []): string
    {
        $this->validateCoordinates($x, $z);
        $document = $this->document($content);
        $root = $document->documentElement;
        if (! $root || $root->tagName !== 'map') {
            throw new RuntimeException('Očekáván je kořenový element map.');
        }
        $group = $document->createElement('group');
        $group->setAttribute('name', $groupName);
        $group->setAttribute('pos', $this->number($x).' '.$this->number((float) ($parameters['pos_y'] ?? 0)).' '.$this->number($z));
        $group->setAttribute('rpy', implode(' ', array_map(fn ($value) => $this->number((float) $value), [
            $parameters['pitch'] ?? 0, $parameters['yaw'] ?? 0, $parameters['roll'] ?? 0,
        ])));
        $group->setAttribute('a', $this->number((float) ($parameters['orientation'] ?? 0)));
        $root->appendChild($group);

        return $this->save($document);
    }

    /** @param array<string, mixed>|float|int $parameters */
    public function appendContaminatedArea(string $content, string $areaName, float $x, float $z, array|float|int $parameters = []): string
    {
        $this->validateCoordinates($x, $z);
        $parameters = is_array($parameters) ? $parameters : ['radius' => $parameters];
        $radius = (float) ($parameters['radius'] ?? 100);
        if ($radius < 1 || $radius > 5000) {
            throw new RuntimeException('Poloměr zóny musí být v rozsahu 1–5000 metrů.');
        }
        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('cfgeffectarea.json není validní JSON: '.$exception->getMessage(), previous: $exception);
        }
        if (! is_array($data) || ! isset($data['Areas']) || ! is_array($data['Areas'])) {
            throw new RuntimeException('cfgeffectarea.json neobsahuje očekávané pole Areas.');
        }
        $data['Areas'][] = [
            'AreaName' => $areaName,
            'Type' => 'ContaminatedArea_Static',
            'TriggerType' => 'ContaminatedTrigger',
            'Data' => [
                'Pos' => [$x, (float) ($parameters['pos_y'] ?? 0), $z],
                'Radius' => $radius,
                'PosHeight' => (float) ($parameters['pos_height'] ?? 20),
                'NegHeight' => (float) ($parameters['neg_height'] ?? 3),
                'InnerPartDist' => (float) ($parameters['inner_part_dist'] ?? 80),
                'OuterOffset' => (float) ($parameters['outer_offset'] ?? 30),
                'ParticleName' => (string) ($parameters['particle_name'] ?? 'graphics/particles/contaminated_area_gas_bigass'),
            ],
            'PlayerData' => [
                'AroundPartName' => (string) ($parameters['around_particle'] ?? 'graphics/particles/contaminated_area_gas_around'),
                'TinyPartName' => (string) ($parameters['tiny_particle'] ?? 'graphics/particles/contaminated_area_gas_around_tiny'),
                'PPERequesterType' => (string) ($parameters['ppe_type'] ?? 'PPERequester_ContaminatedAreaTint'),
            ],
        ];

        try {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Aktualizovaný cfgeffectarea.json se nepodařilo sestavit.', previous: $exception);
        }
    }

    /** @return array{0:DOMDocument,1:DOMElement} */
    private function node(string $content, string $path): array
    {
        if ($path === '' || ! str_starts_with($path, '/')) {
            throw new RuntimeException('Chybí bezpečná cesta k bodu v XML.');
        }
        $document = $this->document($content);
        $node = (new DOMXPath($document))->query($path)->item(0);
        if (! $node instanceof DOMElement) {
            throw new RuntimeException('Bod už v aktuální revizi neexistuje.');
        }

        return [$document, $node];
    }

    private function document(string $content): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('Mapová XML konfigurace není validní.');
        }

        return $document;
    }

    private function save(DOMDocument $document): string
    {
        return $document->saveXML() ?: throw new RuntimeException('XML se nepodařilo sestavit.');
    }

    private function validateCoordinates(float $x, float $z): void
    {
        if ($x < 0 || $z < 0 || $x > MapConfigurationReader::WORLD_SIZE || $z > MapConfigurationReader::WORLD_SIZE) {
            throw new RuntimeException('Souřadnice X/Z musí být v rozsahu 0–15360.');
        }
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
