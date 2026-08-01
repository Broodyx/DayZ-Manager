<?php

namespace App\Services\Revision;

use App\Services\Xml\XmlValidator;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Visual editor for cfgenvironment.xml — the file that registers which animal/infected
 * "territory" (herd or ambient population) exists on the server: its behavior class, the
 * *_territories.xml file its zones live in, the classnames it spawns (per agent/gender), and
 * count knobs like globalCountMax/zoneCountMin/zoneCountMax/herdsCount.
 *
 * Root shape:
 * <env><territories>
 *   <file path="env/wolf_territories.xml" />                      (flat list, loads the file)
 *   <territory type="Herd" name="Wolf" behavior="DZWolfGroupBeh">
 *     <file usable="wolf_territories" />                          (no folder, no extension)
 *     <agent type="Male" chance="1"><spawn configName="Animal_.." chance="1" /></agent>
 *     <item name="globalCountMax" val="50" />
 *   </territory>
 * </territories></env>
 */
final readonly class EnvironmentXmlEditor
{
    public function __construct(private XmlValidator $xmlValidator) {}

    public function supports(string $filename, string $content): bool
    {
        if (strtolower(basename($filename)) !== 'cfgenvironment.xml') {
            return false;
        }

        try {
            return $this->document($content)->documentElement?->tagName === 'env';
        } catch (ValidationException) {
            return false;
        }
    }

    /** @return list<array<string, mixed>> */
    public function entries(string $content): array
    {
        $entries = [];
        foreach ($this->territoryElements($this->document($content)) as $territory) {
            $agents = $this->agentElements($territory);
            $entries[] = [
                'name' => $territory->getAttribute('name'),
                'type' => $territory->getAttribute('type'),
                'behavior' => $territory->getAttribute('behavior'),
                'file' => $this->fileUsable($territory),
                'agent_count' => count($agents),
                'is_infected' => $this->isInfected($territory),
            ];
        }

        return $entries;
    }

    /** @return array<string, mixed> */
    public function values(string $content, string $name): array
    {
        $territory = $this->findTerritory($this->document($content), $name);

        return [
            'name' => $territory->getAttribute('name'),
            'type' => $territory->getAttribute('type'),
            'behavior' => $territory->getAttribute('behavior'),
            'file' => $this->fileUsable($territory),
            'agents' => $this->readAgents($territory),
            'items' => $this->readItems($territory),
            'is_infected' => $this->isInfected($territory),
        ];
    }

    /** @return list<string> basenames referenced by the flat &lt;file path=".."/&gt; list */
    public function fileReferences(string $content): array
    {
        $document = $this->document($content);
        $references = [];
        foreach ($this->territoriesRoot($document)->childNodes as $node) {
            if ($node instanceof DOMElement && $node->tagName === 'file' && $node->hasAttribute('path')) {
                $references[] = basename(str_replace('\\', '/', $node->getAttribute('path')));
            }
        }

        return $references;
    }

    /**
     * Species → target *_territories.xml catalog, used to replace the hardcoded animal list
     * on the map editor so newly registered territories become pickable automatically.
     *
     * @return list<array{name:string,label:string,file:string,type:string,is_infected:bool}>
     */
    public function territoryTargets(string $content): array
    {
        $targets = [];
        foreach ($this->territoryElements($this->document($content)) as $territory) {
            $file = $this->fileUsable($territory);
            if ($file === '') {
                continue;
            }
            $name = $territory->getAttribute('name');
            $targets[] = [
                'name' => $name,
                'label' => $name.' · '.$file,
                'file' => $file,
                'type' => $territory->getAttribute('type'),
                'is_infected' => $this->isInfected($territory),
            ];
        }

        return $targets;
    }

    /** @param array<string, mixed> $values */
    public function update(string $content, string $name, array $values): string
    {
        $document = $this->document($content);
        $territory = $this->findTerritory($document, $name);

        if (array_key_exists('type', $values) && trim((string) $values['type']) !== '') {
            $territory->setAttribute('type', (string) $values['type']);
        }
        if (array_key_exists('behavior', $values) && trim((string) $values['behavior']) !== '') {
            $this->validateBehavior((string) $values['behavior']);
            $territory->setAttribute('behavior', (string) $values['behavior']);
        }
        if (array_key_exists('file', $values) && trim((string) $values['file']) !== '') {
            $this->setFileUsable($document, $territory, (string) $values['file']);
        }
        if (array_key_exists('agents', $values) && is_array($values['agents'])) {
            $this->replaceAgents($document, $territory, $values['agents']);
        }
        if (array_key_exists('items', $values) && is_array($values['items'])) {
            $this->replaceItems($document, $territory, $values['items'], directOnly: true);
        }

        return $this->save($document);
    }

    /** @param array<string, mixed> $values */
    public function addTerritory(string $content, array $values): string
    {
        $name = trim((string) ($values['name'] ?? ''));
        if ($name === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            throw new RuntimeException('Název zvířete smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.');
        }

        $type = trim((string) ($values['type'] ?? ''));
        if (! in_array($type, ['Herd', 'Ambient'], true)) {
            throw new RuntimeException('Typ musí být Herd nebo Ambient.');
        }

        $behavior = trim((string) ($values['behavior'] ?? ''));
        $this->validateBehavior($behavior);

        $document = $this->document($content);
        $root = $this->territoriesRoot($document);

        foreach ($this->territoryElements($document) as $existing) {
            if (strcasecmp($existing->getAttribute('name'), $name) === 0) {
                throw new RuntimeException("Zvíře '{$name}' už v cfgenvironment.xml existuje.");
            }
        }

        $territory = $document->createElement('territory');
        $territory->setAttribute('type', $type);
        $territory->setAttribute('name', $name);
        $territory->setAttribute('behavior', $behavior);
        $root->appendChild($territory);

        $file = trim((string) ($values['file'] ?? ''));
        if ($file !== '') {
            $this->setFileUsable($document, $territory, $file);
            $this->ensureFileReference($document, $root, $file);
        }
        if (isset($values['agents']) && is_array($values['agents'])) {
            $this->replaceAgents($document, $territory, $values['agents']);
        }
        if (isset($values['items']) && is_array($values['items'])) {
            $this->replaceItems($document, $territory, $values['items'], directOnly: true);
        }

        return $this->save($document);
    }

    public function removeTerritory(string $content, string $name): string
    {
        $document = $this->document($content);
        $territory = $this->findTerritory($document, $name);
        $territory->parentNode?->removeChild($territory);

        return $this->save($document);
    }

    private function validateBehavior(string $behavior): void
    {
        if ($behavior === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $behavior)) {
            throw new RuntimeException('Behavior smí obsahovat jen písmena, čísla a podtržítko.');
        }
    }

    /** @param list<array<string, mixed>> $agents */
    private function replaceAgents(DOMDocument $document, DOMElement $territory, array $agents): void
    {
        foreach (iterator_to_array($territory->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'agent') {
                $territory->removeChild($child);
            }
        }

        foreach ($agents as $agent) {
            $agentType = trim((string) ($agent['type'] ?? ''));
            if ($agentType === '' || ! preg_match('/^[A-Za-z0-9_]+$/', $agentType)) {
                throw new RuntimeException('Typ agenta smí obsahovat jen písmena, čísla a podtržítko.');
            }
            $agentElement = $document->createElement('agent');
            $agentElement->setAttribute('type', $agentType);
            $chance = $agent['chance'] ?? null;
            if ($chance !== null && $chance !== '') {
                $agentElement->setAttribute('chance', $this->numeric('chance', $chance));
            }
            foreach ((array) ($agent['spawns'] ?? []) as $spawn) {
                $configName = trim((string) ($spawn['configName'] ?? ''));
                if ($configName === '') {
                    continue;
                }
                if (! preg_match('/^[A-Za-z0-9_.-]+$/', $configName)) {
                    throw new RuntimeException("Classname '{$configName}' smí obsahovat jen písmena, čísla, tečku, pomlčku a podtržítko.");
                }
                $spawnElement = $document->createElement('spawn');
                $spawnElement->setAttribute('configName', $configName);
                $spawnChance = $spawn['chance'] ?? null;
                if ($spawnChance !== null && $spawnChance !== '') {
                    $spawnElement->setAttribute('chance', $this->numeric('chance', $spawnChance));
                }
                $agentElement->appendChild($spawnElement);
            }
            $this->replaceItems($document, $agentElement, (array) ($agent['items'] ?? []), directOnly: false);
            $territory->appendChild($agentElement);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  bool  $directOnly  when true, only direct &lt;item&gt; children are touched (territory-level
     *                            items), leaving items nested inside &lt;agent&gt; untouched
     */
    private function replaceItems(DOMDocument $document, DOMElement $parent, array $items, bool $directOnly): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'item') {
                $parent->removeChild($child);
            }
        }

        foreach ($items as $item) {
            $itemName = trim((string) ($item['name'] ?? ''));
            if ($itemName === '') {
                continue;
            }
            if (! preg_match('/^[A-Za-z0-9_]+$/', $itemName)) {
                throw new RuntimeException("Klíč '{$itemName}' smí obsahovat jen písmena, čísla a podtržítko.");
            }
            $itemElement = $document->createElement('item');
            $itemElement->setAttribute('name', $itemName);
            $itemElement->setAttribute('val', $this->numeric($itemName, $item['val'] ?? 0));
            $parent->appendChild($itemElement);
        }
    }

    private function numeric(string $label, mixed $value): string
    {
        if (! is_numeric($value)) {
            throw new RuntimeException("Hodnota '{$label}' musí být číslo.");
        }
        $float = (float) $value;

        return floor($float) === $float ? (string) (int) $float : rtrim(rtrim(number_format($float, 3, '.', ''), '0'), '.');
    }

    private function setFileUsable(DOMDocument $document, DOMElement $territory, string $file): void
    {
        $usable = pathinfo(basename(str_replace('\\', '/', $file)), PATHINFO_FILENAME);
        if ($usable === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $usable)) {
            throw new RuntimeException('Napojený soubor teritorií má neplatný název.');
        }
        $existing = null;
        foreach ($territory->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'file' && $child->hasAttribute('usable')) {
                $existing = $child;
                break;
            }
        }
        if (! $existing instanceof DOMElement) {
            $existing = $document->createElement('file');
            $territory->insertBefore($existing, $territory->firstChild);
        }
        $existing->setAttribute('usable', $usable);
    }

    private function ensureFileReference(DOMDocument $document, DOMElement $root, string $file): void
    {
        $filename = basename(str_replace('\\', '/', $file));
        if (! str_ends_with(strtolower($filename), '.xml')) {
            $filename .= '.xml';
        }
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'file' && $child->hasAttribute('path')
                && strtolower(basename($child->getAttribute('path'))) === strtolower($filename)) {
                return;
            }
        }
        $fileElement = $document->createElement('file');
        $fileElement->setAttribute('path', 'env/'.$filename);
        $root->insertBefore($fileElement, $root->firstChild);
    }

    private function fileUsable(DOMElement $territory): string
    {
        foreach ($territory->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'file' && $child->hasAttribute('usable')) {
                return $child->getAttribute('usable').'.xml';
            }
        }

        return '';
    }

    /** @return list<array<string, mixed>> */
    private function readAgents(DOMElement $territory): array
    {
        $agents = [];
        foreach ($this->agentElements($territory) as $agent) {
            $spawns = [];
            foreach ($agent->childNodes as $child) {
                if ($child instanceof DOMElement && $child->tagName === 'spawn') {
                    $spawns[] = [
                        'configName' => $child->getAttribute('configName'),
                        'chance' => $child->hasAttribute('chance') ? $child->getAttribute('chance') : '',
                    ];
                }
            }
            $agents[] = [
                'type' => $agent->getAttribute('type'),
                'chance' => $agent->hasAttribute('chance') ? $agent->getAttribute('chance') : '',
                'spawns' => $spawns,
                'items' => $this->readItems($agent),
            ];
        }

        return $agents;
    }

    /** @return list<array{name:string,val:string}> only direct &lt;item&gt; children of $element */
    private function readItems(DOMElement $element): array
    {
        $items = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'item') {
                $items[] = [
                    'name' => $child->getAttribute('name'),
                    'val' => $child->getAttribute('val'),
                ];
            }
        }

        return $items;
    }

    /** @return list<DOMElement> */
    private function territoryElements(DOMDocument $document): array
    {
        $elements = [];
        foreach ($this->territoriesRoot($document)->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'territory') {
                $elements[] = $child;
            }
        }

        return $elements;
    }

    /** @return list<DOMElement> */
    private function agentElements(DOMElement $territory): array
    {
        $agents = [];
        foreach ($territory->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'agent') {
                $agents[] = $child;
            }
        }

        return $agents;
    }

    private function isInfected(DOMElement $territory): bool
    {
        $haystack = strtolower($territory->getAttribute('name').' '.$territory->getAttribute('behavior').' '.$this->fileUsable($territory));

        return str_contains($haystack, 'zombie') || str_contains($haystack, 'infected');
    }

    private function findTerritory(DOMDocument $document, string $name): DOMElement
    {
        foreach ($this->territoryElements($document) as $territory) {
            if ($territory->getAttribute('name') === $name) {
                return $territory;
            }
        }

        throw new RuntimeException("Zvíře '{$name}' v cfgenvironment.xml neexistuje.");
    }

    private function territoriesRoot(DOMDocument $document): DOMElement
    {
        $root = $document->documentElement;
        if (! $root instanceof DOMElement || $root->tagName !== 'env') {
            throw new RuntimeException('Očekáván je kořenový element env.');
        }
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'territories') {
                return $child;
            }
        }

        throw new RuntimeException('cfgenvironment.xml neobsahuje očekávaný element territories.');
    }

    private function document(string $content): DOMDocument
    {
        $validation = $this->xmlValidator->validate($content);
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
        $document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT);

        return $document;
    }

    private function save(DOMDocument $document): string
    {
        return $document->saveXML() ?: throw new RuntimeException('cfgenvironment.xml se nepodařilo sestavit.');
    }
}
