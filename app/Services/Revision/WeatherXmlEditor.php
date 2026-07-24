<?php

namespace App\Services\Revision;

use App\Services\Xml\XmlValidator;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class WeatherXmlEditor
{
    private const PHENOMENA = ['overcast', 'fog', 'rain', 'windMagnitude', 'windDirection', 'snowfall'];

    public function __construct(private XmlValidator $xmlValidator) {}

    /**
     * @return array<string, float|int|bool>
     */
    public function values(string $xml): array
    {
        $document = $this->document($xml);
        $root = $document->documentElement;
        $values = [
            'reset' => $this->booleanAttribute($root, 'reset'),
            'enable' => $this->booleanAttribute($root, 'enable', true),
        ];

        foreach (self::PHENOMENA as $phenomenon) {
            $section = $this->directChild($root, $phenomenon);
            if (! $section) {
                continue;
            }

            foreach ([
                'current' => ['actual', 'time', 'duration'],
                'limits' => ['min', 'max'],
                'timelimits' => ['min', 'max'],
                'changelimits' => ['min', 'max'],
                'thresholds' => ['min', 'max', 'end'],
            ] as $childName => $attributes) {
                $child = $this->directChild($section, $childName);
                if (! $child) {
                    continue;
                }
                foreach ($attributes as $attribute) {
                    if ($child->hasAttribute($attribute)) {
                        $values["{$phenomenon}_{$childName}_{$attribute}"] = (float) $child->getAttribute($attribute);
                    }
                }
            }
        }

        $storm = $this->directChild($root, 'storm');
        if ($storm) {
            foreach (['density', 'threshold', 'timeout'] as $attribute) {
                $values["storm_{$attribute}"] = (float) $storm->getAttribute($attribute);
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(string $xml, array $values): string
    {
        $document = $this->document($xml);
        $root = $document->documentElement;
        $root->setAttribute('reset', ! empty($values['reset']) ? '1' : '0');
        $root->setAttribute('enable', ! empty($values['enable']) ? '1' : '0');

        foreach ($values as $path => $value) {
            if (in_array($path, ['reset', 'enable'], true)) {
                continue;
            }

            $segments = explode('_', $path);
            if ($segments[0] === 'storm' && count($segments) === 2) {
                $this->ensureChild($document, $root, 'storm')->setAttribute($segments[1], (string) $value);

                continue;
            }

            if (count($segments) !== 3 || ! in_array($segments[0], self::PHENOMENA, true)) {
                continue;
            }

            $section = $this->ensureChild($document, $root, $segments[0]);
            $child = $this->ensureChild($document, $section, $segments[1]);
            $child->setAttribute($segments[2], (string) $value);
        }

        $output = $document->saveXML();
        if ($output === false) {
            throw new RuntimeException('Upravené počasí se nepodařilo vytvořit.');
        }

        return $output;
    }

    public function supports(string $filename, string $content): bool
    {
        try {
            return $this->document($content)->documentElement?->tagName === 'weather';
        } catch (ValidationException) {
            return false;
        }
    }

    private function document(string $xml): DOMDocument
    {
        $validation = $this->xmlValidator->validate($xml);
        if (! $validation->valid) {
            throw ValidationException::withMessages(['rawContent' => 'Soubor cfgweather.xml není platné XML.']);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        if ($document->documentElement?->tagName !== 'weather') {
            throw ValidationException::withMessages(['rawContent' => 'Kořenový element musí být <weather>.']);
        }

        return $document;
    }

    private function directChild(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $name) {
                return $child;
            }
        }

        return null;
    }

    private function ensureChild(DOMDocument $document, DOMElement $parent, string $name): DOMElement
    {
        return $this->directChild($parent, $name) ?? $parent->appendChild($document->createElement($name));
    }

    private function booleanAttribute(DOMElement $element, string $name, bool $default = false): bool
    {
        if (! $element->hasAttribute($name)) {
            return $default;
        }

        return in_array(strtolower($element->getAttribute($name)), ['1', 'true', 'yes'], true);
    }
}
