<?php

namespace App\Services\Dayz;

use DOMDocument;
use DOMElement;
use DOMXPath;
use JsonException;
use RuntimeException;

final class MapConfigurationEditor
{
    public function updateCoordinates(string $filename, string $content, string $path, float $x, float $z): string
    {
        $this->validateCoordinates($x, $z);
        [$document, $node] = $this->node($content, $path);

        if ($node->hasAttribute('x') && $node->hasAttribute('z')) {
            $node->setAttribute('x', $this->number($x));
            $node->setAttribute('z', $this->number($z));
        } elseif (strtolower(basename($filename)) === 'mapgrouppos.xml' && $node->hasAttribute('pos')) {
            $parts = preg_split('/\s+/', trim($node->getAttribute('pos'))) ?: [];
            if (count($parts) < 3) {
                throw new RuntimeException('Atribut pos nemá očekávaný formát X Y Z.');
            }
            $parts[0] = $this->number($x);
            $parts[2] = $this->number($z);
            $node->setAttribute('pos', implode(' ', $parts));
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

    public function appendPlayerSpawnArea(string $content, string $groupName, float $x, float $z, string $mode = 'fresh'): string
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
        $position = $document->createElement('pos');
        $position->setAttribute('x', $this->number($x));
        $position->setAttribute('z', $this->number($z));
        $group->appendChild($position);

        return $this->save($document);
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
