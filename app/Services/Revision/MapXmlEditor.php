<?php

namespace App\Services\Revision;

use DOMDocument;
use DOMElement;
use RuntimeException;

final class MapXmlEditor
{
    /** @return array{xml: string, x: float, z: float, a: float} */
    public function appendPosition(string $xml, string $rootEvent, float $x, float $z, float $a = 0): array
    {
        $this->validateCoordinates($x, $z, $a);
        $document = $this->document($xml);
        $root = $document->documentElement;
        if ($root?->tagName !== 'eventposdef') {
            throw new RuntimeException('Očekáván je kořenový element eventposdef.');
        }
        $event = null;
        foreach ($root->getElementsByTagName('event') as $candidate) {
            if ($candidate instanceof DOMElement && $candidate->getAttribute('name') === $rootEvent) {
                $event = $candidate;
                break;
            }
        }
        if (! $event) {
            $event = $document->createElement('event');
            $event->setAttribute('name', $rootEvent);
            $root->appendChild($event);
        }
        $position = $document->createElement('pos');
        $position->setAttribute('x', $this->number($x));
        $position->setAttribute('z', $this->number($z));
        $position->setAttribute('a', $this->number($a));
        $event->appendChild($position);

        return ['xml' => $this->save($document), 'x' => $x, 'z' => $z, 'a' => $a];
    }

    private function document(string $xml): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA)) {
            throw new RuntimeException('XML konfigurace není validní.');
        }

        return $document;
    }

    private function save(DOMDocument $document): string
    {
        $xml = $document->saveXML();
        if ($xml === false || ! str_contains($xml, '<')) {
            throw new RuntimeException('XML se nepodařilo uložit.');
        }

        return $xml;
    }

    private function validateCoordinates(float $x, float $z, float $a): void
    {
        if ($x < 0 || $x > 15360 || $z < 0 || $z > 15360 || $a < -360 || $a > 360) {
            throw new RuntimeException('Souřadnice musí být v rozsahu 0–15360 a natočení -360–360.');
        }
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
