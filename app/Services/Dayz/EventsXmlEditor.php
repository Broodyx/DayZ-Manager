<?php

namespace App\Services\Dayz;

use DOMDocument;
use DOMElement;
use RuntimeException;

final class EventsXmlEditor
{
    /** @param array<string, mixed> $values */
    public function appendEvent(string $content, string $name, array $values): string
    {
        $name = trim($name);
        if ($name === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            throw new RuntimeException('Název eventu smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('events.xml není validní XML.');
        }
        $root = $document->documentElement;
        if (! $root || $root->tagName !== 'events') {
            throw new RuntimeException('Očekáván je kořenový element events.');
        }
        foreach ($root->getElementsByTagName('event') as $existing) {
            if ($existing instanceof DOMElement && strtolower($existing->getAttribute('name')) === strtolower($name)) {
                throw new RuntimeException("Event '{$name}' už v events.xml existuje.");
            }
        }

        $integer = fn (string $key, int $default): int => max(0, (int) ($values[$key] ?? $default));

        $event = $document->createElement('event');
        $event->setAttribute('name', $name);
        foreach ([
            'nominal' => $integer('nominal', 1),
            'min' => $integer('min', 0),
            'max' => $integer('max', 1),
            'lifetime' => $integer('lifetime', 3600),
            'restock' => $integer('restock', 0),
            'saferadius' => $integer('saferadius', 100),
            'distanceradius' => $integer('distanceradius', 100),
            'cleanupradius' => $integer('cleanupradius', 100),
        ] as $tag => $value) {
            $event->appendChild($document->createElement($tag, (string) $value));
        }

        $flags = $document->createElement('flags');
        $flags->setAttribute('deletable', ($values['deletable'] ?? false) ? '1' : '0');
        $flags->setAttribute('init_random', ($values['init_random'] ?? true) ? '1' : '0');
        $flags->setAttribute('remove_damaged', ($values['remove_damaged'] ?? false) ? '1' : '0');
        $event->appendChild($flags);

        $position = in_array($values['position'] ?? 'fixed', ['fixed', 'player'], true) ? $values['position'] : 'fixed';
        $event->appendChild($document->createElement('position', $position));

        $limit = in_array($values['limit'] ?? 'mixed', ['mixed', 'unlimited', 'nearest', 'farthest'], true) ? $values['limit'] : 'mixed';
        $event->appendChild($document->createElement('limit', $limit));
        $event->appendChild($document->createElement('active', '1'));

        $children = $document->createElement('children');
        $childType = trim((string) ($values['child_type'] ?? ''));
        if ($childType !== '') {
            if (! preg_match('/^[A-Za-z0-9_.-]+$/', $childType)) {
                throw new RuntimeException('Classname objektu smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.');
            }
            $child = $document->createElement('child');
            $child->setAttribute('lootmax', '0');
            $child->setAttribute('lootmin', '0');
            $child->setAttribute('max', '1');
            $child->setAttribute('min', '1');
            $child->setAttribute('type', $childType);
            $children->appendChild($child);
        }
        $event->appendChild($children);

        $root->appendChild($event);

        return $document->saveXML() ?: throw new RuntimeException('events.xml se nepodařilo sestavit.');
    }
}
