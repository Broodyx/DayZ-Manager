<?php

namespace App\Services\Dayz;

use DOMDocument;
use DOMElement;
use DOMXPath;
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

    public function appendPlayerSpawnArea(string $content, string $groupName, float $x, float $z): string
    {
        $this->validateCoordinates($x, $z);
        $document = $this->document($content);
        $xpath = new DOMXPath($document);
        $container = $xpath->query('/playerspawnpoints/fresh/generator_posbubbles')->item(0);
        if (! $container instanceof DOMElement) {
            throw new RuntimeException('V cfgplayerspawnpoints.xml chybí fresh/generator_posbubbles.');
        }
        $group = null;
        foreach ($xpath->query('/playerspawnpoints/fresh/generator_posbubbles/group') ?: [] as $candidate) {
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
