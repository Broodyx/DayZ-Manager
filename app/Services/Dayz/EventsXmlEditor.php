<?php

namespace App\Services\Dayz;

use DOMDocument;
use DOMElement;
use RuntimeException;

final class EventsXmlEditor
{
    private const FIELDS = ['nominal', 'min', 'max', 'lifetime', 'restock', 'saferadius', 'distanceradius', 'cleanupradius'];
    private const FLAGS = ['deletable', 'init_random', 'remove_damaged'];

    public function supports(string $filename, string $content): bool
    {
        if (strtolower(basename($filename)) !== 'events.xml') {
            return false;
        }
        $document = new DOMDocument();
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT)) {
            return false;
        }

        return $document->documentElement?->tagName === 'events';
    }

    /** Lightweight summary of every event, cheap enough to list hundreds at once. */
    public function entries(string $content): array
    {
        $document = $this->document($content);
        $entries = [];
        foreach ($document->getElementsByTagName('event') as $event) {
            if (! $event instanceof DOMElement || ! $event->hasAttribute('name')) {
                continue;
            }
            $entries[] = [
                'name' => $event->getAttribute('name'),
                'nominal' => $this->childInteger($event, 'nominal'),
                'min' => $this->childInteger($event, 'min'),
                'max' => $this->childInteger($event, 'max'),
                'children_count' => $event->getElementsByTagName('child')->length,
            ];
        }

        return $entries;
    }

    /** Full editable field set for exactly one event. */
    public function values(string $content, string $name): array
    {
        $event = $this->find($this->document($content), $name);
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = $this->childInteger($event, $field);
        }
        $flags = $event->getElementsByTagName('flags')->item(0);
        foreach (self::FLAGS as $flag) {
            $values[$flag] = $flags instanceof DOMElement ? (bool) (int) $flags->getAttribute($flag) : false;
        }
        $values['position'] = $this->childText($event, 'position') ?: 'fixed';
        $values['limit'] = $this->childText($event, 'limit') ?: 'mixed';
        $values['active'] = $this->childText($event, 'active') !== '0';
        $children = [];
        foreach ($event->getElementsByTagName('child') as $child) {
            if ($child instanceof DOMElement) {
                $children[] = [
                    'type' => $child->getAttribute('type'),
                    'min' => (int) $child->getAttribute('min'),
                    'max' => (int) $child->getAttribute('max'),
                    'lootmin' => (int) $child->getAttribute('lootmin'),
                    'lootmax' => (int) $child->getAttribute('lootmax'),
                ];
            }
        }
        $values['children'] = $children;

        return $values;
    }

    /** @param array<string, mixed> $values */
    public function update(string $content, string $name, array $values): string
    {
        $document = $this->document($content);
        $event = $this->find($document, $name);

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $element = $event->getElementsByTagName($field)->item(0);
            if (! $element instanceof DOMElement) {
                $element = $document->createElement($field);
                $event->appendChild($element);
            }
            $element->nodeValue = (string) max(0, (int) $values[$field]);
        }

        $flags = $event->getElementsByTagName('flags')->item(0);
        if (! $flags instanceof DOMElement) {
            $flags = $document->createElement('flags');
            $event->appendChild($flags);
        }
        foreach (self::FLAGS as $flag) {
            if (array_key_exists($flag, $values)) {
                $flags->setAttribute($flag, $values[$flag] ? '1' : '0');
            }
        }

        if (isset($values['position'])) {
            $this->setChildText($document, $event, 'position', in_array($values['position'], ['fixed', 'player'], true) ? $values['position'] : 'fixed');
        }
        if (isset($values['limit'])) {
            $this->setChildText($document, $event, 'limit', in_array($values['limit'], ['mixed', 'custom', 'child', 'parent'], true) ? $values['limit'] : 'mixed');
        }
        if (array_key_exists('active', $values)) {
            $this->setChildText($document, $event, 'active', $values['active'] ? '1' : '0');
        }

        // Renames the classname this event spawns — the common single-child case this UI
        // actually exposes (a full children[] replace is available above for anything more
        // elaborate, but nothing currently sends it). Preserves min/max/lootmin/lootmax on the
        // existing <child>; only its type attribute changes.
        if (isset($values['child_classname']) && trim((string) $values['child_classname']) !== '') {
            $newType = trim((string) $values['child_classname']);
            if (! preg_match('/^[A-Za-z0-9_.-]+$/', $newType)) {
                throw new RuntimeException("Classname '{$newType}' smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.");
            }
            $firstChild = $event->getElementsByTagName('child')->item(0);
            if ($firstChild instanceof DOMElement) {
                $firstChild->setAttribute('type', $newType);
            }
        }

        if (isset($values['children']) && is_array($values['children'])) {
            $childrenElement = $event->getElementsByTagName('children')->item(0);
            if (! $childrenElement instanceof DOMElement) {
                $childrenElement = $document->createElement('children');
                $event->appendChild($childrenElement);
            }
            foreach (iterator_to_array($childrenElement->childNodes) as $existingChild) {
                $childrenElement->removeChild($existingChild);
            }
            foreach ($values['children'] as $child) {
                $type = trim((string) ($child['type'] ?? ''));
                if ($type === '') {
                    continue;
                }
                if (! preg_match('/^[A-Za-z0-9_.-]+$/', $type)) {
                    throw new RuntimeException("Classname '{$type}' smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.");
                }
                $childElement = $document->createElement('child');
                $childElement->setAttribute('type', $type);
                $childElement->setAttribute('min', (string) max(0, (int) ($child['min'] ?? 0)));
                $childElement->setAttribute('max', (string) max(0, (int) ($child['max'] ?? 0)));
                $childElement->setAttribute('lootmin', (string) max(0, (int) ($child['lootmin'] ?? 0)));
                $childElement->setAttribute('lootmax', (string) max(0, (int) ($child['lootmax'] ?? 0)));
                $childrenElement->appendChild($childElement);
            }
        }

        return $document->saveXML() ?: throw new RuntimeException('events.xml se nepodařilo sestavit.');
    }

    public function remove(string $content, string $name): string
    {
        $document = $this->document($content);
        $event = $this->find($document, $name);
        $event->parentNode?->removeChild($event);

        return $document->saveXML() ?: throw new RuntimeException('events.xml se nepodařilo sestavit.');
    }

    /** @param array<string, mixed> $values */
    public function appendEvent(string $content, string $name, array $values): string
    {
        $name = trim($name);
        if ($name === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            throw new RuntimeException('Název eventu smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.');
        }

        $document = $this->document($content);
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

        $requestedPosition = $values['position'] ?? 'fixed';
        $position = in_array($requestedPosition, ['fixed', 'player'], true) ? $requestedPosition : 'fixed';
        $event->appendChild($document->createElement('position', $position));

        $requestedLimit = $values['limit'] ?? 'mixed';
        $limit = in_array($requestedLimit, ['mixed', 'custom', 'child', 'parent'], true) ? $requestedLimit : 'mixed';
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

    private function document(string $content): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new RuntimeException('events.xml není validní XML.');
        }

        return $document;
    }

    private function find(DOMDocument $document, string $name): DOMElement
    {
        // trim() both sides — a stray leading/trailing space in either the requested name or
        // the file's own name="..." attribute (invisible in normal viewing, easy to introduce
        // by hand-editing) would otherwise fail this lookup for an event that visibly matches.
        $needle = trim($name);
        foreach ($document->getElementsByTagName('event') as $event) {
            if ($event instanceof DOMElement && trim($event->getAttribute('name')) === $needle) {
                return $event;
            }
        }

        throw new RuntimeException("Event '{$name}' už v events.xml neexistuje.");
    }

    private function childInteger(DOMElement $event, string $name): int
    {
        $element = $event->getElementsByTagName($name)->item(0);

        return $element instanceof DOMElement ? (int) trim($element->textContent) : 0;
    }

    private function childText(DOMElement $event, string $name): string
    {
        $element = $event->getElementsByTagName($name)->item(0);

        return $element instanceof DOMElement ? trim($element->textContent) : '';
    }

    private function setChildText(DOMDocument $document, DOMElement $event, string $name, string $value): void
    {
        $element = $event->getElementsByTagName($name)->item(0);
        if (! $element instanceof DOMElement) {
            $element = $document->createElement($name);
            $event->appendChild($element);
        }
        $element->nodeValue = $value;
    }
}
