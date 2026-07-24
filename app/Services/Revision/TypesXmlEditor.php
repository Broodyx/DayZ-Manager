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
     * @return list<array{name: string, nominal: int, lifetime: int, restock: int, min: int, quantmin: int, quantmax: int, cost: int}>
     */
    public function entries(string $xml): array
    {
        $document = $this->document($xml);
        $entries = [];

        foreach ($document->getElementsByTagName('type') as $type) {
            if (! $type instanceof DOMElement || ! $type->hasAttribute('name')) {
                continue;
            }

            $entry = ['name' => $type->getAttribute('name')];
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

    public function supports(string $filename, string $content): bool
    {
        if (strtolower(basename($filename)) !== 'types.xml') {
            return false;
        }

        try {
            return $this->entries($content) !== [];
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
}
