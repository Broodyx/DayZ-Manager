<?php

namespace App\Services\Revision;

use App\Services\Xml\XmlValidator;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Validation\ValidationException;

final readonly class XmlConfigurationEditor
{
    public function __construct(private XmlValidator $validator) {}

    public function supports(string $content): bool
    {
        try {
            $document = $this->document($content);
        } catch (ValidationException) {
            return false;
        }

        return $document->documentElement?->tagName !== null;
    }

    /** @return list<array{path:string,section:string,label:string,type:string,value:mixed,raw:string}> */
    public function fields(string $content): array
    {
        $document = $this->document($content);
        $fields = [];
        $this->walk($document->documentElement, '', $fields);

        return $fields;
    }

    public function update(string $content, array $values): string
    {
        $document = $this->document($content);
        $xpath = new DOMXPath($document);
        foreach ($values as $path => $value) {
            if (! str_contains($path, '@') && ! str_ends_with($path, '/text()')) {
                continue;
            }
            if (str_ends_with($path, '/text()')) {
                $nodes = $xpath->query(substr($path, 0, -7));
                if ($nodes?->length) {
                    $nodes->item(0)->nodeValue = (string) $value;
                }

                continue;
            }
            [$nodePath, $attribute] = explode('@', $path, 2);
            $nodes = $xpath->query($nodePath);
            if ($nodes?->length && $nodes->item(0) instanceof DOMElement) {
                $nodes->item(0)->setAttribute($attribute, (string) $value);
            }
        }

        return $document->saveXML() ?: $content;
    }

    private function document(string $content): DOMDocument
    {
        if (! $this->validator->validate($content)->valid) {
            throw ValidationException::withMessages(['rawContent' => 'XML není platné.']);
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT);

        return $document;
    }

    private function walk(DOMElement $element, string $path, array &$fields): void
    {
        $current = $path === '' ? '/'.$element->tagName.'[1]' : $path;
        foreach ($element->attributes as $attribute) {
            $fields[] = ['path' => $current.'@'.$attribute->name, 'section' => $element->tagName, 'label' => str($attribute->name)->headline()->toString(), 'type' => is_numeric($attribute->value) ? 'number' : 'text', 'value' => is_numeric($attribute->value) ? (float) $attribute->value : $attribute->value, 'raw' => $current.'@'.$attribute->name];
        }
        $hasElementChild = false;
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $hasElementChild = true;
                break;
            }
        }
        if (! $hasElementChild && trim($element->textContent) !== '') {
            $value = trim($element->textContent);
            $fields[] = ['path' => $current.'/text()', 'section' => $element->tagName, 'label' => str($element->tagName)->headline()->toString(), 'type' => is_numeric($value) ? 'number' : 'text', 'value' => is_numeric($value) ? (float) $value : $value, 'raw' => '<'.$element->tagName.'>'.$value.'</'.$element->tagName.'>'];
        }
        $counts = [];
        foreach ($element->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            $counts[$child->tagName] = ($counts[$child->tagName] ?? 0) + 1;
            $this->walk($child, $current.'/'.$child->tagName.'['.$counts[$child->tagName].']', $fields);
        }
    }
}
