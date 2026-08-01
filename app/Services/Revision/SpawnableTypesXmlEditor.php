<?php

namespace App\Services\Revision;

use App\Services\Xml\XmlValidator;
use DOMDocument;
use DOMElement;
use RuntimeException;

final readonly class SpawnableTypesXmlEditor
{
    public function __construct(private XmlValidator $validator) {}

    /** @return array{damage_min:?float,damage_max:?float,cargo:array<int,array<string,mixed>>,attachments:array<int,array<string,mixed>>,hoarder:bool} */
    public function values(string $xml, string $typeName): array
    {
        $type = $this->find($this->document($xml), $typeName);
        $damage = $type->getElementsByTagName('damage')->item(0);
        $cargo = [];
        foreach ($type->getElementsByTagName('cargo') as $node) {
            if (! $node instanceof DOMElement) continue;
            $items = [];
            foreach ($node->getElementsByTagName('item') as $item) {
                if ($item instanceof DOMElement) $items[] = ['name' => $item->getAttribute('name'), 'chance' => (float) ($item->getAttribute('chance') ?: 1)];
            }
            $cargo[] = ['chance' => (float) ($node->getAttribute('chance') ?: 1), 'preset' => $node->getAttribute('preset'), 'items' => $items];
        }
        $attachments = [];
        foreach ($type->getElementsByTagName('attachments') as $node) {
            if (! $node instanceof DOMElement) continue;
            $items = [];
            foreach ($node->getElementsByTagName('item') as $item) {
                if ($item instanceof DOMElement) $items[] = ['name' => $item->getAttribute('name'), 'chance' => (float) ($item->getAttribute('chance') ?: 1)];
            }
            $attachments[] = ['chance' => (float) ($node->getAttribute('chance') ?: 1), 'items' => $items];
        }

        return ['damage_min' => $damage instanceof DOMElement ? (float) $damage->getAttribute('min') : null, 'damage_max' => $damage instanceof DOMElement ? (float) $damage->getAttribute('max') : null, 'cargo' => $cargo, 'attachments' => $attachments, 'hoarder' => $type->getElementsByTagName('hoarder')->length > 0];
    }

    /** @param array<string,mixed> $values */
    public function update(string $xml, string $typeName, array $values): string
    {
        $document = $this->document($xml);
        try {
            $type = $this->find($document, $typeName);
        } catch (RuntimeException) {
            $root = $document->documentElement;
            if (! $root instanceof DOMElement || $root->tagName !== 'spawnabletypes') throw new RuntimeException('cfgspawnabletypes.xml nemá očekávaný kořen.');
            $type = $document->createElement('type');
            $type->setAttribute('name', $typeName);
            $root->appendChild($type);
        }
        foreach (['damage', 'cargo', 'attachments', 'hoarder'] as $tag) {
            foreach (iterator_to_array($type->getElementsByTagName($tag)) as $node) $node->parentNode?->removeChild($node);
        }
        if ($values['damage_min'] !== null || $values['damage_max'] !== null) {
            $damage = $document->createElement('damage');
            $damage->setAttribute('min', (string) max(0, min(1, (float) ($values['damage_min'] ?? 0))));
            $damage->setAttribute('max', (string) max(0, min(1, (float) ($values['damage_max'] ?? 0))));
            $type->appendChild($damage);
        }
        if (! empty($values['hoarder'])) $type->appendChild($document->createElement('hoarder'));
        foreach ((array) ($values['attachments'] ?? []) as $group) {
            $node = $document->createElement('attachments');
            $node->setAttribute('chance', (string) max(0, min(1, (float) ($group['chance'] ?? 1))));
            foreach ((array) ($group['items'] ?? []) as $item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') continue;
                $child = $document->createElement('item');
                $child->setAttribute('name', $name);
                $child->setAttribute('chance', (string) max(0, min(1, (float) ($item['chance'] ?? 1))));
                $node->appendChild($child);
            }
            $type->appendChild($node);
        }
        foreach ((array) ($values['cargo'] ?? []) as $group) {
            $preset = trim((string) ($group['preset'] ?? ''));
            $items = (array) ($group['items'] ?? []);
            if ($preset === '' && $items === []) continue;
            $node = $document->createElement('cargo');
            $node->setAttribute('chance', (string) max(0, min(1, (float) ($group['chance'] ?? 1))));
            if ($preset !== '') {
                $node->setAttribute('preset', $preset);
            } else {
                foreach ($items as $item) {
                    $name = trim((string) ($item['name'] ?? ''));
                    if ($name === '') continue;
                    $child = $document->createElement('item');
                    $child->setAttribute('name', $name);
                    $child->setAttribute('chance', (string) max(0, min(1, (float) ($item['chance'] ?? 1))));
                    $node->appendChild($child);
                }
            }
            $type->appendChild($node);
        }
        return $document->saveXML() ?: throw new RuntimeException('cfgspawnabletypes.xml se nepodařilo sestavit.');
    }

    private function document(string $xml): DOMDocument
    {
        $result = $this->validator->validate($xml);
        if (! $result->valid) throw new RuntimeException('cfgspawnabletypes.xml není validní XML.');
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false; $document->formatOutput = true;
        $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        return $document;
    }

    private function find(DOMDocument $document, string $name): DOMElement
    {
        foreach ($document->getElementsByTagName('type') as $type) if ($type instanceof DOMElement && $type->getAttribute('name') === $name) return $type;
        throw new RuntimeException("Položka {$name} v cfgspawnabletypes.xml neexistuje.");
    }
}
