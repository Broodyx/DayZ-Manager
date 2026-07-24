<?php

namespace App\Services\Revision;

use App\Services\Xml\XmlValidator;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class TypesXmlEditor
{
    private const FIELDS = ['nominal', 'lifetime', 'restock', 'min', 'quantmin', 'quantmax', 'cost'];

    public function __construct(private XmlValidator $xmlValidator) {}

    /**
     * @return list<array{name: string, category: string, usages: list<string>, pc_only: bool, nominal: int, lifetime: int, restock: int, min: int, quantmin: int, quantmax: int, cost: int}>
     */
    public function entries(string $xml): array
    {
        $document = $this->document($xml);
        $entries = [];

        foreach ($document->getElementsByTagName('type') as $type) {
            if (! $type instanceof DOMElement || ! $type->hasAttribute('name')) {
                continue;
            }

            $entry = [
                'name' => $type->getAttribute('name'),
                'category' => $this->childAttribute($type, 'category', 'name') ?: 'other',
                'usages' => $this->childAttributes($type, 'usage', 'name'),
                'pc_only' => $this->isPcOnly($document, $type),
            ];
            foreach (self::FIELDS as $field) {
                $entry[$field] = $this->childInteger($type, $field);
            }
            $entries[] = $entry;
        }

        return $entries;
    }

    /**
     * @param  array<string, int>  $values
     */
    public function update(string $xml, string $typeName, array $values): string
    {
        $document = $this->document($xml);
        $matched = null;

        foreach ($document->getElementsByTagName('type') as $type) {
            if ($type instanceof DOMElement && $type->getAttribute('name') === $typeName) {
                $matched = $type;
                break;
            }
        }

        if (! $matched) {
            throw new RuntimeException("Položka {$typeName} už v XML neexistuje.");
        }

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $element = $matched->getElementsByTagName($field)->item(0);
            if (! $element instanceof DOMElement) {
                $element = $document->createElement($field);
                $matched->appendChild($element);
            }
            $element->nodeValue = (string) $values[$field];
        }

        $output = $document->saveXML();
        if ($output === false) {
            throw new RuntimeException('Upravené XML se nepodařilo vytvořit.');
        }

        return $output;
    }

    /**
     * @param  array<string, int>  $values
     * @param  list<string>  $usages
     */
    public function add(
        string $xml,
        string $typeName,
        array $values,
        string $category,
        array $usages = [],
    ): string {
        $document = $this->document($xml);

        foreach ($document->getElementsByTagName('type') as $type) {
            if ($type instanceof DOMElement && strcasecmp($type->getAttribute('name'), $typeName) === 0) {
                throw ValidationException::withMessages([
                    'newTypeForm.name' => "Položka {$typeName} už v konfiguraci existuje.",
                ]);
            }
        }

        $root = $document->documentElement;
        if (! $root instanceof DOMElement || $root->tagName !== 'types') {
            throw ValidationException::withMessages([
                'newTypeForm.name' => 'Soubor nemá očekávaný kořenový element <types>.',
            ]);
        }

        $type = $document->createElement('type');
        $type->setAttribute('name', $typeName);

        foreach (self::FIELDS as $field) {
            $type->appendChild($document->createElement($field, (string) ($values[$field] ?? 0)));
        }

        $flags = $document->createElement('flags');
        foreach ([
            'count_in_cargo' => '0',
            'count_in_hoarder' => '0',
            'count_in_map' => '1',
            'count_in_player' => '0',
            'crafted' => '0',
            'deloot' => '0',
        ] as $name => $value) {
            $flags->setAttribute($name, $value);
        }
        $type->appendChild($flags);

        $categoryElement = $document->createElement('category');
        $categoryElement->setAttribute('name', $category);
        $type->appendChild($categoryElement);

        foreach (array_values(array_unique($usages)) as $usage) {
            $usageElement = $document->createElement('usage');
            $usageElement->setAttribute('name', $usage);
            $type->appendChild($usageElement);
        }

        $root->appendChild($type);
        $output = $document->saveXML();
        if ($output === false) {
            throw new RuntimeException('XML s novou položkou se nepodařilo vytvořit.');
        }

        return $output;
    }

    public function supports(string $filename, string $content): bool
    {
        try {
            $document = $this->document($content);

            return $document->documentElement?->tagName === 'types' && $this->entries($content) !== [];
        } catch (ValidationException) {
            return false;
        }
    }

    private function document(string $xml): DOMDocument
    {
        $validation = $this->xmlValidator->validate($xml);
        if (! $validation->valid) {
            throw ValidationException::withMessages([
                'rawContent' => array_map(
                    static fn (array $error): string => sprintf(
                        'Řádek %d: %s',
                        (int) ($error['line'] ?? 0),
                        $error['message'] ?? 'Neplatné XML.',
                    ),
                    $validation->errors,
                ),
            ]);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);

        return $document;
    }

    private function childInteger(DOMElement $type, string $name): int
    {
        $element = $type->getElementsByTagName($name)->item(0);

        return $element instanceof DOMElement ? (int) $element->textContent : 0;
    }

    private function childAttribute(DOMElement $type, string $elementName, string $attribute): ?string
    {
        $element = $type->getElementsByTagName($elementName)->item(0);

        return $element instanceof DOMElement && $element->hasAttribute($attribute)
            ? $element->getAttribute($attribute)
            : null;
    }

    /**
     * @return list<string>
     */
    private function childAttributes(DOMElement $type, string $elementName, string $attribute): array
    {
        $values = [];
        foreach ($type->getElementsByTagName($elementName) as $element) {
            if ($element instanceof DOMElement && $element->hasAttribute($attribute)) {
                $values[] = $element->getAttribute($attribute);
            }
        }

        return $values;
    }

    private function isPcOnly(DOMDocument $document, DOMElement $type): bool
    {
        $content = strtolower($document->saveXML($type) ?: '');

        return str_contains($content, 'steamcommunity.com')
            || preg_match('/(?:^|[\s"\'])-mod\s*=/i', $content) === 1
            || preg_match('/(?:^|[\/\\\\])@[a-z0-9_.-]+/i', $content) === 1
            || preg_match('/\b(cftools|community framework|dayz expansion)\b/i', $content) === 1;
    }
}
