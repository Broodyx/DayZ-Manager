<?php

namespace App\Services\Revision;

use App\Services\Xml\XmlValidator;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;

final readonly class EventGroupsXmlEditor
{
    public function __construct(private XmlValidator $validator) {}

    public function supports(string $filename, string $content): bool
    {
        if (strtolower(basename($filename)) !== 'cfgeventgroups.xml') {
            return false;
        }

        return $this->validator->validate($content)->valid;
    }

    /**
     * @return list<array{
     *   name:string,
     *   name_path:string,
     *   children:list<array<string, array{path:string,value:string|float|int}>>
     * }>
     */
    public function groups(string $content): array
    {
        if (! $this->validator->validate($content)->valid) {
            throw ValidationException::withMessages(['rawContent' => 'cfgeventgroups.xml není platné XML.']);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT);
        $root = $document->documentElement;
        if (! $root) {
            return [];
        }

        $groups = [];
        $groupIndex = 0;
        foreach ($root->childNodes as $group) {
            if (! $group instanceof DOMElement || $group->tagName !== 'group') {
                continue;
            }

            $groupIndex++;
            $groupPath = '/'.$root->tagName.'[1]/group['.$groupIndex.']';
            $children = [];
            $childIndex = 0;
            foreach ($group->childNodes as $child) {
                if (! $child instanceof DOMElement || $child->tagName !== 'child') {
                    continue;
                }

                $childIndex++;
                $childPath = $groupPath.'/child['.$childIndex.']';
                $fields = [];
                foreach (['type', 'deloot', 'lootmin', 'lootmax', 'x', 'z', 'a', 'y'] as $attribute) {
                    $raw = $child->getAttribute($attribute);
                    $fields[$attribute] = [
                        'path' => $childPath.'@'.$attribute,
                        'value' => is_numeric($raw) ? (float) $raw : $raw,
                    ];
                }
                $children[] = $fields;
            }

            $groups[] = [
                'name' => $group->getAttribute('name'),
                'name_path' => $groupPath.'@name',
                'children' => $children,
            ];
        }

        return $groups;
    }

    public function addChild(string $content, int $groupIndex, string $type): string
    {
        [$document, $group] = $this->groupElement($content, $groupIndex);
        $child = $document->createElement('child');
        foreach ([
            'type' => $type,
            'deloot' => '0',
            'lootmax' => '0',
            'lootmin' => '0',
            'x' => '0',
            'z' => '0',
            'a' => '0',
            'y' => '0',
        ] as $name => $value) {
            $child->setAttribute($name, $value);
        }
        $group->appendChild($child);

        return $document->saveXML() ?: $content;
    }

    public function removeChild(string $content, int $groupIndex, int $childIndex): string
    {
        [$document, $group] = $this->groupElement($content, $groupIndex);
        $children = [];
        foreach ($group->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'child') {
                $children[] = $child;
            }
        }
        if (! isset($children[$childIndex])) {
            throw ValidationException::withMessages(['xmlValues' => 'Vybraný objekt ve skupině už neexistuje.']);
        }
        $group->removeChild($children[$childIndex]);

        return $document->saveXML() ?: $content;
    }

    /** @return array{DOMDocument, DOMElement} */
    private function groupElement(string $content, int $groupIndex): array
    {
        if (! $this->validator->validate($content)->valid) {
            throw ValidationException::withMessages(['rawContent' => 'cfgeventgroups.xml není platné XML.']);
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        $document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT);
        $groups = [];
        foreach ($document->documentElement?->childNodes ?? [] as $group) {
            if ($group instanceof DOMElement && $group->tagName === 'group') {
                $groups[] = $group;
            }
        }
        if (! isset($groups[$groupIndex])) {
            throw ValidationException::withMessages(['xmlValues' => 'Vybraná skupina už neexistuje.']);
        }

        return [$document, $groups[$groupIndex]];
    }
}
