<?php

namespace App\Filament\Pages;

use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Services\Dayz\ClassnameCatalog;
use App\Services\Dayz\EnvironmentTargetCatalog;
use App\Services\Dayz\EventsXmlEditor;
use App\Services\Dayz\MapConfigurationEditor;
use App\Services\Dayz\MapConfigurationReader;
use App\Services\Dayz\ServerFileLayout;
use App\Services\Ftp\FtpBrowser;
use App\Services\Import\ConfigurationImporter;
use App\Services\Revision\ConfigurationRevisionEditor;
use App\Services\Revision\EnvironmentXmlEditor;
use App\Services\Revision\TypesXmlEditor;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

class MapEditor extends Page
{
    use WithFileUploads;

    protected static string $view = 'filament.pages.map-editor';

    protected static bool $shouldRegisterNavigation = false;

    public string $map = 'Chernarus';

    public ?int $projectId = null;

    public array $projects = [];

    public $mapFile;

    public array $markers = [];

    public array $eventCatalog = [];
    public array $pointTypeCatalog = [];
    public array $mapSources = [];
    public array $markerCounts = [];
    public bool $hasFtpConnection = false;
    public array $loadedSources = [];
    public array $layerScopes = [];
    public bool $showDenseLayers = false;
    public array $spawnPointWarnings = [];
    public array $eventSpawnWarnings = [];
    public array $animalPopulationWarnings = [];
    public array $animalTypeWarnings = [];
    public array $spawnValidationWarnings = [];
    public array $classnameOptions = [];
    public bool $showAddEventModal = false;
    public string $addEventName = '';
    public array $addEventForm = [];
    public array $lootCategoryLegend = [];

    private const CATEGORY_COLORS = [
        'weapons' => '#e96a5f',
        'medical' => '#80b8ff',
        'food' => '#8fd15c',
        'tools' => '#f1b44c',
        'clothes' => '#d58cff',
        'containers' => '#55e0c1',
        'vehicles' => '#f6d365',
        'explosives' => '#ff5a5a',
    ];

    /** Priority order when a building's loot points span several categories. */
    private const CATEGORY_PRIORITY = ['weapons', 'explosives', 'medical', 'food', 'tools', 'clothes', 'containers', 'vehicles'];

    /**
     * Real vanilla Animal_* types.xml entries carry no population of their own — nominal/min/
     * restock are all 0 because the live animal count is driven by events.xml, not the economy
     * restock system. A generated entry mirrors that instead of guessing plausible-looking numbers.
     */
    private const ANIMAL_TYPE_DEFAULTS = ['nominal' => 0, 'lifetime' => 1800, 'restock' => 0, 'min' => 0, 'quantmin' => -1, 'quantmax' => -1, 'cost' => 100];

    public function mount(ClassnameCatalog $classnameCatalog): void
    {
        $this->projects = $this->projectQuery()->orderBy('name')->pluck('name', 'id')->all();
        $this->projectId = request()->integer('project') ?: array_key_first($this->projects);

        // A project with nothing uploaded yet has nothing to plot — send it through the
        // Checklist first, same guard EditConfiguration::mount() already applies, so the map
        // works as the project landing page without ever showing a confusingly empty state.
        if ($this->projectId) {
            $project = $this->projectQuery()->find($this->projectId);
            if ($project && ! $project->revisions()->exists()) {
                $this->redirect(ConfigurationWizard::getUrl(['project' => $project->id]));

                return;
            }
        }

        $this->showDenseLayers = request()->boolean('dense');
        $this->classnameOptions = $classnameCatalog->names();
        $this->loadMarkers();
        $this->loadMapSources();
        $this->loadEventCatalog();
        $this->loadPointTypeCatalog();
        $this->loadSpawnPointWarnings();
        $this->loadEventSpawnWarnings();
        $this->loadAnimalPopulationWarnings();
        $this->loadAnimalTypeWarnings();
        $this->loadSpawnValidationWarnings();

        $requestedEvent = trim((string) request()->string('open_event'));
        if ($requestedEvent !== '' && in_array($requestedEvent, $this->eventSpawnWarnings, true)) {
            $this->openAddEventModal($requestedEvent);
        }
    }

    public function updatedProjectId(): void
    {
        $this->loadMarkers();
        $this->loadEventCatalog();
        $this->loadMapSources();
        $this->loadPointTypeCatalog();
        $this->loadSpawnPointWarnings();
        $this->loadEventSpawnWarnings();
        $this->loadAnimalPopulationWarnings();
        $this->loadAnimalTypeWarnings();
        $this->loadSpawnValidationWarnings();
    }

    /** Re-runs all map spawn relationship checks on demand and keeps the result visible in the banners below. */
    public function checkSpawnEventLinks(): void
    {
        $this->loadEventCatalog();
        $this->loadEventSpawnWarnings();
        $this->loadAnimalPopulationWarnings();
        $this->loadAnimalTypeWarnings();
        $this->loadSpawnValidationWarnings();

        $issues = count($this->eventSpawnWarnings)
            + count($this->animalPopulationWarnings)
            + count($this->animalTypeWarnings)
            + count($this->spawnValidationWarnings);

        if ($issues > 0) {
            Notification::make()
                ->warning()
                ->title("Kontrola našla {$issues} problémů ve vazbách spawnů")
                ->body('Výsledky jsou zobrazené v bannerech pod ovládací lištou mapy.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Vazby spawnů jsou v pořádku')
            ->body('Spawn body odkazují na existující eventy a jejich classy jsou v types.xml.')
            ->send();
    }

    /** Structural checks for spawn candidates; terrain suitability still requires the server RPT. */
    public function loadSpawnValidationWarnings(): void
    {
        $this->spawnValidationWarnings = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $revisions = $project ? $this->latestRevisions($project) : collect();
        $filenameOf = fn ($revision): string => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path)));
        $environmentTargets = app(EnvironmentTargetCatalog::class)->targets($revisions, $filenameOf);
        $registeredTerritoryFiles = collect($environmentTargets)->pluck('file')->map(fn ($file) => strtolower(basename((string) $file)))->all();
        foreach ($this->markers as $marker) {
            $parameters = $marker['parameters'] ?? [];
            if (($marker['type'] ?? '') === 'territory') {
                $filename = strtolower((string) ($marker['filename'] ?? ''));
                if (str_ends_with($filename, '_territories.xml')) {
                    if ($registeredTerritoryFiles !== [] && ! in_array(basename($filename), $registeredTerritoryFiles, true)) {
                        $this->spawnValidationWarnings[] = ['severity' => 'critical', 'title' => 'Territory soubor není registrovaný v cfgenvironment.xml', 'detail' => "{$marker['label']} se načítá z {$filename}, ale tento soubor není v aktuálním cfgenvironment.xml přiřazený žádnému druhu/behavioru.", 'action' => 'V cfgenvironment.xml přidejte <file path="env/..." /> a odpovídající <territory><file usable="..." /></territory>, nebo použijte správný registrovaný soubor.'];
                    }
                    $zone = (string) ($parameters['zone_type'] ?? '');
                    $knownZones = $this->zoneTypeCatalog();
                    if (! in_array($zone, $knownZones, true) && ! str_contains($filename, 'zombie')) {
                        $this->spawnValidationWarnings[] = ['severity' => 'warning', 'title' => 'Neznámá úloha zóny', 'detail' => "{$marker['label']} používá „{$zone}“, která se v aktuálních territory souborech projektu jinde nevyskytuje.", 'action' => 'Ověřte název proti cfgenvironment.xml a odpovídajícímu *_territories.xml; vlastní název může být platný jen pro konkrétní konfiguraci.'];
                    }
                    foreach (['smin' => 'smax', 'dmin' => 'dmax'] as $min => $max) {
                        if ((float) ($parameters[$min] ?? 0) > (float) ($parameters[$max] ?? 0)) {
                            $this->spawnValidationWarnings[] = ['severity' => 'critical', 'title' => 'Neplatný rozsah spawnu', 'detail' => "{$marker['label']} má {$min} větší než {$max}.", 'action' => 'Minimum nesmí být vyšší než maximum.'];
                        }
                    }
                    if ((float) ($parameters['radius'] ?? 0) <= 0) {
                        $this->spawnValidationWarnings[] = ['severity' => 'critical', 'title' => 'Zóna zvířat nemá platný poloměr', 'detail' => "{$marker['label']} má poloměr 0 nebo zápornou hodnotu.", 'action' => 'Nastavte poloměr alespoň 1 metr.'];
                    }
                }
            }
        }
        foreach ($this->eventCatalog as $event) {
            $name = (string) ($event['name'] ?? '');
            if (! Str::startsWith($name, ['Animal', 'Vehicle'])) continue;
            $settings = $event['settings'] ?? [];
            if ((int) ($settings['active'] ?? 0) !== 1 || (int) ($settings['nominal'] ?? 0) <= 0 || (int) ($settings['max'] ?? 0) <= 0) {
                $this->spawnValidationWarnings[] = ['severity' => 'critical', 'title' => "Event {$name} je vypnutý nebo má nulovou populaci", 'detail' => 'Bod může být správně uložený, ale event nevytvoří žádnou instanci.', 'action' => 'V events.xml nastavte active=1 a nominal/max větší než 0.'];
            }
            if ((int) ($settings['min'] ?? 0) > (int) ($settings['max'] ?? 0)) {
                $this->spawnValidationWarnings[] = ['severity' => 'critical', 'title' => "Event {$name} má neplatný rozsah", 'detail' => 'Minimum je vyšší než maximum.', 'action' => 'Opravte min/max v events.xml.'];
            }
        }
    }

    /**
     * Flags animals/ambient species registered in cfgenvironment.xml that have no matching
     * events.xml entry — a territory alone never spawns anything; events.xml is what actually
     * tells the Central Economy how many to keep alive and how often to restock them. Herd
     * species need an "Animal"-prefixed event (Deer → AnimalDeer); Ambient species need an
     * exact-name match (AmbientHen → AmbientHen). Infected/zombie territories are skipped —
     * they spawn through a different mechanism (Infected* events tied to cfgeventspawns.xml).
     */
    public function loadAnimalPopulationWarnings(): void
    {
        $this->animalPopulationWarnings = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'cfgenvironment.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            return;
        }

        try {
            $entries = app(EnvironmentXmlEditor::class)->entries(Storage::disk('dayz')->get($revision->storage_path));
        } catch (\Throwable) {
            return;
        }

        $definedEvents = collect($this->eventCatalog)->pluck('name')->map(fn ($name) => strtolower($name))->all();
        foreach ($entries as $entry) {
            if ($entry['is_infected']) {
                continue;
            }
            $expectedEvent = $entry['type'] === 'Ambient' ? $entry['name'] : 'Animal'.$entry['name'];
            if (! in_array(strtolower($expectedEvent), $definedEvents, true)) {
                $this->animalPopulationWarnings[] = ['territory' => $entry['name'], 'expected_event' => $expectedEvent];
            }
        }
    }

    /**
     * Flags every classname an event/environment actually spawns that has no types.xml entry —
     * the Central Economy needs a types.xml entry to track and persist anything it spawns, so
     * a missing entry is another way an otherwise-correctly-configured animal never appears.
     *
     * Where a species' classnames live differs by type, confirmed against a real vanilla
     * cfgenvironment.xml/events.xml pair: Ambient/Infected species (hen, hare, fox, zombies)
     * list them directly on cfgenvironment.xml's own <agent><spawn configName>. Herd species
     * (deer, wolf, bear, …) have NO <agent> at all in cfgenvironment.xml — their classnames
     * instead live in the matching events.xml event's <children><child type>, the same event
     * loadAnimalPopulationWarnings() already looks for. The same event catalogue also contains
     * vehicles and compound-event objects, so checking all of it is important for map-created
     * vehicle/train/convoy spawns as well. Checking only cfgenvironment.xml's agents silently
     * misses every Herd species and every vehicle event.
     */
    public function loadAnimalTypeWarnings(): void
    {
        $this->animalTypeWarnings = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revisions = $this->latestRevisions($project);
        $environmentRevision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'cfgenvironment.xml');
        if (! $environmentRevision || ! Storage::disk('dayz')->exists($environmentRevision->storage_path)) {
            return;
        }

        $typesRevision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'types.xml');
        $definedTypes = [];
        if ($typesRevision && Storage::disk('dayz')->exists($typesRevision->storage_path)) {
            $definedTypes = collect(app(TypesXmlEditor::class)->entries(Storage::disk('dayz')->get($typesRevision->storage_path)))
                ->pluck('name')->map(fn ($name) => strtolower($name))->all();
        }

        $environmentEditor = app(EnvironmentXmlEditor::class);
        $environmentContent = Storage::disk('dayz')->get($environmentRevision->storage_path);
        try {
            $entries = $environmentEditor->entries($environmentContent);
        } catch (Throwable) {
            return;
        }

        $eventsByName = collect($this->eventCatalog)->keyBy(fn (array $event): string => strtolower($event['name']));

        $seen = [];
        foreach ($entries as $entry) {
            if ($entry['is_infected']) {
                continue;
            }

            $classnames = [];
            try {
                $values = $environmentEditor->values($environmentContent, $entry['name']);
                foreach ($values['agents'] as $agent) {
                    foreach ($agent['spawns'] as $spawn) {
                        $classnames[] = trim((string) ($spawn['configName'] ?? ''));
                    }
                }
            } catch (Throwable) {
                // Fall through — the events.xml children below still get checked either way.
            }

            $expectedEvent = $entry['type'] === 'Ambient' ? $entry['name'] : 'Animal'.$entry['name'];
            $event = $eventsByName->get(strtolower($expectedEvent));
            foreach ($event['children'] ?? [] as $child) {
                $classnames[] = trim((string) ($child['type'] ?? ''));
            }

            foreach (array_unique(array_filter($classnames, fn (string $name): bool => $name !== '')) as $classname) {
                if (isset($seen[$classname])) {
                    continue;
                }
                $seen[$classname] = true;
                if (! in_array(strtolower($classname), $definedTypes, true)) {
                    $this->animalTypeWarnings[] = ['territory' => $entry['name'], 'classname' => $classname];
                }
            }
        }

        // Vehicle, train, convoy and other dynamic event points are defined by the child
        // classes in events.xml (with cfgeventgroups.xml merged into eventCatalog). They are
        // just as dependent on types.xml as animal children, even though they have no
        // cfgenvironment.xml territory.
        foreach ($this->eventCatalog as $event) {
            foreach ($event['children'] ?? [] as $child) {
                $classname = trim((string) ($child['type'] ?? ''));
                if ($classname === '' || isset($seen[$classname])) {
                    continue;
                }
                $seen[$classname] = true;
                if (! in_array(strtolower($classname), $definedTypes, true)) {
                    $this->animalTypeWarnings[] = [
                        'territory' => $event['name'],
                        'classname' => $classname,
                    ];
                }
            }
        }
    }

    /** Generates a types.xml entry for an animal classname cfgenvironment.xml spawns but types.xml doesn't track yet. */
    public function addAnimalTypeEntry(string $classname, TypesXmlEditor $typesEditor, ConfigurationRevisionEditor $revisionEditor): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'types.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            Notification::make()->danger()->title('types.xml nebyl nalezen.')->body('Nejprve nahrajte types.xml přes „Přidat mapový soubor“.')->send();

            return;
        }

        try {
            $updated = $typesEditor->add(
                Storage::disk('dayz')->get($revision->storage_path),
                $classname,
                self::ANIMAL_TYPE_DEFAULTS,
                'other',
            );
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title($exception->validator->errors()->first())->send();

            return;
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        $saved = $revisionEditor->save($project, $revision, $updated, "Přidána položka {$classname} (zvíře z cfgenvironment.xml)", auth()->user());
        $this->loadAnimalTypeWarnings();
        Notification::make()->success()->title("Položka {$classname} přidána do types.xml")->body("Vznikla revize #{$saved->revision_number}.")->send();
    }

    /** Flags event names used in cfgeventspawns.xml that events.xml does not define. */
    public function loadEventSpawnWarnings(): void
    {
        $this->eventSpawnWarnings = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'cfgeventspawns.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            return;
        }
        $xml = @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path));
        if (! $xml) {
            return;
        }
        $definedEvents = collect($this->eventCatalog)->pluck('name')->map(fn ($name) => strtolower($name))->all();
        $usedEvents = [];
        foreach ($xml->event ?? [] as $event) {
            $name = (string) ($event['name'] ?? '');
            if ($name !== '') {
                $usedEvents[$name] = true;
            }
        }
        foreach (array_keys($usedEvents) as $name) {
            if (! in_array(strtolower($name), $definedEvents, true)) {
                $this->eventSpawnWarnings[] = $name;
            }
        }
    }

    /** Removes cfgeventspawns.xml positions for an event that events.xml does not define. */
    public function removeEventSpawnPositions(string $eventName, MapConfigurationEditor $mapEditor, ConfigurationRevisionEditor $revisionEditor): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'cfgeventspawns.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            Notification::make()->danger()->title('cfgeventspawns.xml nebyl nalezen.')->send();

            return;
        }
        try {
            $result = $mapEditor->deleteScope('cfgeventspawns.xml', Storage::disk('dayz')->get($revision->storage_path), 'event:'.$eventName);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }
        $saved = $revisionEditor->save($project, $revision, $result['content'], "Odstraněny pozice eventu {$eventName}", auth()->user());
        $this->loadMarkers();
        $this->loadMapSources();
        $this->loadEventSpawnWarnings();
        Notification::make()->success()->title("Pozice eventu {$eventName} odstraněny")->body("Vznikla revize #{$saved->revision_number}.")->send();
    }

    public function openAddEventModal(string $eventName): void
    {
        $this->addEventName = $eventName;
        $this->addEventForm = [
            'nominal' => 1, 'min' => 0, 'max' => 1,
            'lifetime' => 3600, 'restock' => 0,
            'saferadius' => 100, 'distanceradius' => 100, 'cleanupradius' => 100,
            'position' => 'fixed', 'limit' => 'mixed', 'child_type' => '',
        ];
        $this->showAddEventModal = true;
    }

    /**
     * Same modal as openAddEventModal(), but pre-filled for an animal population event
     * (limit=child instead of mixed, and the classname pulled from the territory's own
     * cfgenvironment.xml agents when available) instead of the generic object-event defaults.
     */
    public function openAddAnimalEventModal(string $territoryName, string $eventName): void
    {
        $childType = '';
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if ($project) {
            $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'cfgenvironment.xml');
            if ($revision && Storage::disk('dayz')->exists($revision->storage_path)) {
                try {
                    $values = app(EnvironmentXmlEditor::class)->values(Storage::disk('dayz')->get($revision->storage_path), $territoryName);
                    $childType = $values['agents'][0]['spawns'][0]['configName'] ?? '';
                } catch (\Throwable) {
                    $childType = '';
                }
            }
        }

        $this->addEventName = $eventName;
        $this->addEventForm = [
            'nominal' => 5, 'min' => 1, 'max' => 3,
            'lifetime' => 3600, 'restock' => 0,
            'saferadius' => 100, 'distanceradius' => 100, 'cleanupradius' => 100,
            'position' => 'fixed', 'limit' => 'child', 'child_type' => $childType,
        ];
        $this->showAddEventModal = true;
    }

    public function closeAddEventModal(): void
    {
        $this->showAddEventModal = false;
    }

    public function submitAddEvent(EventsXmlEditor $editor, ConfigurationRevisionEditor $revisionEditor): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'events.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            Notification::make()->danger()->title('events.xml nebyl nalezen.')->body('Nejprve nahrajte events.xml přes „Přidat mapový soubor“.')->send();

            return;
        }
        try {
            $updated = $editor->appendEvent(Storage::disk('dayz')->get($revision->storage_path), $this->addEventName, $this->addEventForm);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }
        $saved = $revisionEditor->save($project, $revision, $updated, "Přidán event {$this->addEventName}", auth()->user());
        $this->showAddEventModal = false;
        $this->loadEventCatalog();
        $this->loadEventSpawnWarnings();
        $this->loadAnimalPopulationWarnings();
        $this->loadPointTypeCatalog();
        Notification::make()->success()->title("Event {$this->addEventName} byl přidán do events.xml")->body("Vznikla revize #{$saved->revision_number}.")->send();
    }

    /** Flags fresh/hop/travel modes that have zero player spawn positions. */
    public function loadSpawnPointWarnings(): void
    {
        $this->spawnPointWarnings = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }
        $revision = $this->latestRevisions($project)->first(fn ($item) => $this->revisionFilename($item) === 'cfgplayerspawnpoints.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            return;
        }
        $xml = @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path));
        if (! $xml) {
            return;
        }
        $labels = [
            'fresh' => 'FRESH · nová postava',
            'hop' => 'HOP · změna serveru',
            'travel' => 'TRAVEL · cestovní přesun',
        ];
        foreach ($labels as $mode => $label) {
            $modeNode = $xml->{$mode} ?? null;
            $count = 0;
            foreach ($modeNode?->generator_posbubbles->group ?? [] as $group) {
                $count += count($group->pos ?? []);
            }
            if ($modeNode && $count === 0) {
                $this->spawnPointWarnings[] = ['mode' => $mode, 'label' => $label];
            }
        }
    }

    public function loadMapSources(): void
    {
        $definitions = [
            'cfgeventspawns.xml' => ['Pevné kandidátní pozice eventů a jejich orientace.', true],
            'events.xml' => ['Pravidla dynamických eventů, vozidel a heli crashů; neobsahuje mapové souřadnice.', false],
            'cfgeventgroups.xml' => ['Složení a varianty eventů; neobsahuje mapové souřadnice.', false],
            'cfgplayerspawnpoints.xml' => ['Oblasti generátoru spawnů hráčů. Body nejsou přesná místa zrození.', true],
            'mapgrouppos.xml' => ['Světové pozice loot skupin a budov.', true],
            'mapgroupcluster.xml' => ['Definice clusterů budov; relativní data se do mapy nekreslí.', false],
            'mapgroupcluster01.xml' => ['Definice clusterů budov – část 1.', false],
            'mapgroupcluster02.xml' => ['Definice clusterů budov – část 2.', false],
            'mapgroupcluster03.xml' => ['Definice clusterů budov – část 3.', false],
            'mapgroupcluster04.xml' => ['Definice clusterů budov – část 4.', false],
            'mapgroupproto.xml' => ['Prototypy skupin; souřadnice jsou relativní, nikoli světové.', false],
            'mapclusterproto.xml' => ['Prototypy clusterů; souřadnice jsou relativní, nikoli světové.', false],
            'mapgroupdirt.xml' => ['Doplňková prototypová data bez světových bodů.', false],
            'cfgeffectarea.json' => ['Efektové a kontaminované oblasti.', true],
            'cfgundergroundtriggers.json' => ['Spouštěče podzemních oblastí.', true],
            '*spawner*.json' => ['Object Spawner: vlastní objekty, pozice a orientace.', true],
            '*_territories.xml' => ['Teritoria zvířat podle druhu.', true],
        ];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $this->hasFtpConnection = (bool) $project?->hasFtpConnection();
        $revisions = $this->latestRevisions($project);
        $this->mapSources = [];
        foreach ($definitions as $filename => [$description, $plottable]) {
            $matches = $revisions->filter(function ($item) use ($filename) {
                $name = $this->revisionFilename($item);
                return str_contains($filename, '*') ? Str::is($filename, $name) : $name === $filename;
            });
            if ($matches->isEmpty()) {
                $this->mapSources[] = $this->source($filename, $description, $plottable);
                continue;
            }
            foreach ($matches as $revision) {
                $actualName = $this->revisionFilename($revision);
                $this->mapSources[] = $this->source($actualName, $description, $plottable, $revision);
            }
        }
    }

    public function loadEventCatalog(): void
    {
        $this->eventCatalog = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $revisions = $this->latestRevisions($project);
        $byName = fn (string $name) => $revisions->first(fn ($item) => $this->revisionFilename($item) === $name);
        $events = $byName('events.xml');
        if ($events && Storage::disk('dayz')->exists($events->storage_path)) {
            $xml = @simplexml_load_string(Storage::disk('dayz')->get($events->storage_path));
            foreach ($xml?->event ?? [] as $event) {
                $children = [];
                foreach ($event->children->child ?? [] as $child) {
                    $children[] = [
                        'type' => (string) ($child['type'] ?? ''),
                        'min' => (int) ($child['min'] ?? 0),
                        'max' => (int) ($child['max'] ?? 0),
                        'lootmin' => (int) ($child['lootmin'] ?? 0),
                        'lootmax' => (int) ($child['lootmax'] ?? 0),
                    ];
                }
                $this->eventCatalog[] = [
                    'name' => (string) $event['name'],
                    'children' => array_values(array_filter($children, fn ($child) => $child['type'] !== '')),
                    'settings' => [
                        'nominal' => (int) ($event->nominal ?? 0),
                        'min' => (int) ($event->min ?? 0),
                        'max' => (int) ($event->max ?? 0),
                        'lifetime' => (int) ($event->lifetime ?? 0),
                        'restock' => (int) ($event->restock ?? 0),
                        'saferadius' => (int) ($event->saferadius ?? 0),
                        'distanceradius' => (int) ($event->distanceradius ?? 0),
                        'cleanupradius' => (int) ($event->cleanupradius ?? 0),
                        'position' => (string) ($event->position ?? ''),
                        'limit' => (string) ($event->limit ?? ''),
                        'active' => (int) ($event->active ?? 0),
                        'deletable' => (int) ($event->flags['deletable'] ?? 0),
                        'init_random' => (int) ($event->flags['init_random'] ?? 0),
                        'remove_damaged' => (int) ($event->flags['remove_damaged'] ?? 0),
                    ],
                ];
            }
        }

        // cfgeventgroups.xml is the authoritative catalogue for compound events
        // (trains, convoys and their individual vehicle/object classes).
        $groups = $byName('cfgeventgroups.xml');
        if ($groups && Storage::disk('dayz')->exists($groups->storage_path)) {
            $xml = @simplexml_load_string(Storage::disk('dayz')->get($groups->storage_path));
            foreach ($xml?->group ?? [] as $group) {
                $children = [];
                foreach ($group->child ?? [] as $child) {
                    $type = (string) ($child['type'] ?? '');
                    if ($type !== '') {
                        $children[] = $type;
                    }
                }
                $name = (string) ($group['name'] ?? '');
                if ($name === '') continue;
                $index = collect($this->eventCatalog)->search(fn ($item) => $item['name'] === $name);
                if ($index !== false) {
                    $known = collect($this->eventCatalog[$index]['children'])->pluck('type')->all();
                    foreach ($children as $type) {
                        if (! in_array($type, $known, true)) {
                            $this->eventCatalog[$index]['children'][] = ['type' => $type, 'min' => 0, 'max' => 0, 'lootmin' => 0, 'lootmax' => 0];
                        }
                    }
                }
            }
        }
    }

    public function loadPointTypeCatalog(): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        $revisions = $this->latestRevisions($project);
        $uploaded = $revisions->mapWithKeys(fn ($revision) => [
            $this->revisionFilename($revision) => [
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
            ],
        ])->all();
        $uploadUrl = fn (string $filename) => url('/admin/configuration-import?area=map&project='.$this->projectId.'&expected='.urlencode($filename));
        $exact = function (string $filename, array $options, string $help, array $recommended = []) use ($uploaded, $uploadUrl): array {
            $missing = array_values(array_filter([$filename, ...$recommended], fn ($name) => ! isset($uploaded[$name])));

            return [
                'target' => $filename,
                'target_label' => $filename,
                'available' => isset($uploaded[$filename]) && $missing === [],
                'missing' => $missing,
                'upload_url' => $uploadUrl($missing[0] ?? $filename),
                'options' => $options,
                'help' => $help,
            ];
        };
        $eventOptions = fn ($events, string $target) => collect($events)->map(fn ($event) => [
            'value' => $event['name'],
            'label' => $event['name'],
            'target' => $target,
            'available' => isset($uploaded[$target]),
            'event_settings' => $event['settings'] ?? [],
            'children' => $event['children'] ?? [],
        ])->values()->all();
        $options = fn ($names, string $target) => collect($names)->map(fn ($name) => [
            'value' => $name, 'label' => $name, 'target' => $target, 'available' => isset($uploaded[$target]),
        ])->values()->all();
        $environmentTargets = app(EnvironmentTargetCatalog::class)->targets($revisions, fn ($item) => $this->revisionFilename($item));
        $animalOptions = collect($environmentTargets)
            ->reject(fn (array $item) => $item['is_infected'])
            ->map(fn (array $item) => [
                'value' => $item['name'],
                'label' => $item['name'].' · '.$item['file'],
                'target' => $item['file'],
                'available' => isset($uploaded[$item['file']]),
                'upload_url' => $uploadUrl($item['file']),
                'event_name' => 'Animal'.$item['name'],
                'event_settings' => collect($this->eventCatalog)->first(fn (array $event): bool => strtolower($event['name']) === strtolower('Animal'.$item['name']))['settings'] ?? [],
                'children' => collect($this->eventCatalog)->first(fn (array $event): bool => strtolower($event['name']) === strtolower('Animal'.$item['name']))['children'] ?? [],
            ])->values()->all();
        $infectedOptions = collect($environmentTargets)
            ->filter(fn (array $item) => $item['is_infected'])
            ->map(fn (array $item) => [
                'value' => $item['name'],
                'label' => $item['name'].' · '.$item['file'],
                'target' => $item['file'],
                'available' => isset($uploaded[$item['file']]),
                'upload_url' => $uploadUrl($item['file']),
            ])->values()->all();
        $territoryOptions = collect(array_keys($uploaded))
            ->filter(fn ($name) => Str::is('*_territories.xml', $name))
            ->map(fn ($name) => ['value' => 'HuntingGround', 'label' => $name, 'target' => $name, 'available' => true])
            ->values()->all();
        $lootNames = [];
        if (isset($uploaded['mapgroupproto.xml'])) {
            $revision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'mapgroupproto.xml');
            if ($revision && Storage::disk('dayz')->exists($revision->storage_path)) {
                $xml = @simplexml_load_string(Storage::disk('dayz')->get($revision->storage_path));
                foreach ($xml?->group ?? [] as $group) {
                    $name = (string) ($group['name'] ?? '');
                    if ($name !== '') $lootNames[] = $name;
                }
            }
        }

        $eventsBy = fn ($callback) => collect($this->eventCatalog)->filter($callback)->values();
        $eventFields = [
            ['name' => 'orientation', 'label' => 'Natočení objektu (°)', 'type' => 'number', 'min' => 0, 'max' => 359.999, 'step' => 0.001, 'default' => 0, 'required' => true, 'help' => 'Povinné. 0° míří na sever; hodnota určuje natočení kandidátní pozice.'],
        ];
        $playerFields = [
            ['name' => 'spawn_mode', 'section' => 'Bod a skupina', 'label' => 'Režim spawnu', 'type' => 'select', 'default' => 'fresh', 'options' => [
                ['value' => 'fresh', 'label' => 'fresh · nová postava'],
                ['value' => 'hop', 'label' => 'hop · změna serveru'],
                ['value' => 'travel', 'label' => 'travel · cestovní přesun'],
            ], 'help' => 'Sekce XML. Parametry režimu níže platí pro všechny jeho skupiny a body.'],
            ['name' => 'group_name', 'section' => 'Bod a skupina', 'label' => 'Název skupiny oblastí', 'type' => 'text', 'default' => 'Vlastni oblast', 'help' => 'Více pozic se stejným názvem tvoří jednu skupinu generátoru.'],
            ['name' => 'group_lifetime_override', 'section' => 'Bod a skupina', 'label' => 'Lifetime skupiny – přepis (s)', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => '', 'help' => 'Volitelný atribut skupiny. Prázdné = použít hodnotu režimu; -1 = bez časového vypršení.'],
            ['name' => 'group_counter_override', 'section' => 'Bod a skupina', 'label' => 'Counter skupiny – přepis', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => '', 'help' => 'Volitelný limit použití skupiny. Prázdné = hodnota režimu; -1 = bez limitu.'],
            ['name' => 'min_dist_infected', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Min. vzdálenost od nakažených (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 30, 'help' => 'Pod touto vzdáleností je kandidát neplatný. Musí být ≤ maximu.'],
            ['name' => 'max_dist_infected', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Max. vzdálenost od nakažených (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 70, 'help' => 'Od minima do maxima získává kandidát lepší hodnocení; nad maximem už bez další výhody.'],
            ['name' => 'min_dist_player', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Min. vzdálenost od hráčů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 25, 'help' => 'Pod touto vzdáleností od jiného hráče se kandidát nepoužije.'],
            ['name' => 'max_dist_player', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Max. vzdálenost od hráčů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 70, 'help' => 'Horní mez hodnocení bezpečné vzdálenosti od ostatních hráčů.'],
            ['name' => 'min_dist_static', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Min. vzdálenost od statických objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 0, 'help' => 'Minimální odstup kandidáta od statické geometrie při vyhodnocení spawnu.'],
            ['name' => 'max_dist_static', 'section' => 'Bezpečné vzdálenosti · spawn_params', 'label' => 'Max. vzdálenost od statických objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 2, 'help' => 'Horní mez hodnocení odstupu od statické geometrie.'],
            ['name' => 'grid_density', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Hustota mřížky', 'type' => 'number', 'min' => 1, 'max' => 1000, 'step' => 1, 'default' => 4, 'help' => 'Počet testovaných kandidátů v mřížce. Vyšší hodnota zpřesňuje hledání, ale zvyšuje práci serveru.'],
            ['name' => 'grid_width', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Šířka oblasti (m)', 'type' => 'number', 'min' => 1, 'max' => 15360, 'step' => 1, 'default' => 200, 'help' => 'Celková šířka prohledávané oblasti kolem každého bodu; není to poloměr.'],
            ['name' => 'grid_height', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Výška oblasti (m)', 'type' => 'number', 'min' => 1, 'max' => 15360, 'step' => 1, 'default' => 200, 'help' => 'Celková výška prohledávané oblasti kolem každého bodu; nejde o nadmořskou výšku.'],
            ['name' => 'generator_min_dist_static', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Generátor: min. odstup od objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 0, 'help' => 'Kandidátní buňky blíže statické geometrii jsou odmítnuty.'],
            ['name' => 'generator_max_dist_static', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Generátor: max. odstup od objektů (m)', 'type' => 'number', 'min' => 0, 'max' => 15360, 'step' => 0.1, 'default' => 2, 'help' => 'Horní mez hodnocení odstupu kandidátní buňky od statické geometrie.'],
            ['name' => 'min_steepness', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Minimální sklon (°)', 'type' => 'number', 'min' => -90, 'max' => 90, 'step' => 0.1, 'default' => -45, 'help' => 'Dolní povolená mez sklonu povrchu v rozsahu -90 až 90°.'],
            ['name' => 'max_steepness', 'section' => 'Generátor kandidátů · generator_params', 'label' => 'Maximální sklon (°)', 'type' => 'number', 'min' => -90, 'max' => 90, 'step' => 0.1, 'default' => 45, 'help' => 'Horní povolená mez sklonu; musí být ≥ minimálnímu sklonu.'],
            ['name' => 'enablegroups', 'section' => 'Chování skupin · group_params', 'label' => 'Používat skupiny', 'type' => 'select', 'default' => 'true', 'options' => [
                ['value' => 'true', 'label' => 'true · skupiny zapnuté'],
                ['value' => 'false', 'label' => 'false · skupiny vypnuté'],
            ], 'help' => 'Určuje, zda generátor používá pojmenované skupiny z generator_posbubbles.'],
            ['name' => 'groups_as_regular', 'section' => 'Chování skupin · group_params', 'label' => 'Skupiny jako běžné oblasti', 'type' => 'select', 'default' => 'true', 'options' => [
                ['value' => 'true', 'label' => 'true · použít jako běžné oblasti'],
                ['value' => 'false', 'label' => 'false · zvláštní skupinové chování'],
            ], 'help' => 'Řídí, zda se skupinové oblasti vyhodnocují stejným způsobem jako pravidelné kandidátní oblasti.'],
            ['name' => 'lifetime', 'section' => 'Chování skupin · group_params', 'label' => 'Lifetime režimu (s)', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => 120, 'help' => 'Jak dlouho zůstává volba skupiny aktivní. -1 vypíná časové vypršení.'],
            ['name' => 'counter', 'section' => 'Chování skupin · group_params', 'label' => 'Counter režimu', 'type' => 'number', 'min' => -1, 'max' => 2147483647, 'step' => 1, 'default' => 2, 'help' => 'Kolikrát lze skupinu použít před dalším výběrem. -1 vypíná limit počtu.'],
        ];
        $playerModeDefaults = [];
        $playerRevision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'cfgplayerspawnpoints.xml');
        if ($playerRevision && Storage::disk('dayz')->exists($playerRevision->storage_path)) {
            $playerXml = @simplexml_load_string(Storage::disk('dayz')->get($playerRevision->storage_path));
            foreach (['fresh', 'hop', 'travel'] as $mode) {
                $modeNode = $playerXml?->{$mode};
                if (! $modeNode) {
                    continue;
                }
                $playerModeDefaults[$mode] = [
                    'min_dist_infected' => (string) ($modeNode->spawn_params->min_dist_infected ?? ''),
                    'max_dist_infected' => (string) ($modeNode->spawn_params->max_dist_infected ?? ''),
                    'min_dist_player' => (string) ($modeNode->spawn_params->min_dist_player ?? ''),
                    'max_dist_player' => (string) ($modeNode->spawn_params->max_dist_player ?? ''),
                    'min_dist_static' => (string) ($modeNode->spawn_params->min_dist_static ?? ''),
                    'max_dist_static' => (string) ($modeNode->spawn_params->max_dist_static ?? ''),
                    'grid_density' => (string) ($modeNode->generator_params->grid_density ?? ''),
                    'grid_width' => (string) ($modeNode->generator_params->grid_width ?? ''),
                    'grid_height' => (string) ($modeNode->generator_params->grid_height ?? ''),
                    'generator_min_dist_static' => (string) ($modeNode->generator_params->min_dist_static ?? ''),
                    'generator_max_dist_static' => (string) ($modeNode->generator_params->max_dist_static ?? ''),
                    'min_steepness' => (string) ($modeNode->generator_params->min_steepness ?? ''),
                    'max_steepness' => (string) ($modeNode->generator_params->max_steepness ?? ''),
                    'enablegroups' => (string) ($modeNode->group_params->enablegroups ?? ''),
                    'groups_as_regular' => (string) ($modeNode->group_params->groups_as_regular ?? ''),
                    'lifetime' => (string) ($modeNode->group_params->lifetime ?? ''),
                    'counter' => (string) ($modeNode->group_params->counter ?? ''),
                ];
            }
        }
        $territoryFields = [
            ['name' => 'zone_type', 'label' => 'Úloha zóny', 'type' => 'text', 'list' => 'dz-zone-type-catalog', 'default' => 'Graze', 'required' => true, 'help' => 'Povinné. U stád zvířat použij Graze, Water nebo Rest; u nakažených přesný tier, např. InfectedVillageTier1.'],
            ['name' => 'radius', 'label' => 'Poloměr (m)', 'type' => 'number', 'min' => 1, 'max' => 5000, 'step' => 0.5, 'default' => 150, 'help' => 'Atribut r: dosah zóny od středu v metrech.'],
            ['name' => 'smin', 'label' => 'Statický spawn minimum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut smin. Minimální počet statických výskytů pro zónu.'],
            ['name' => 'smax', 'label' => 'Statický spawn maximum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut smax. Maximální počet statických výskytů pro zónu.'],
            ['name' => 'dmin', 'label' => 'Dynamický spawn minimum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut dmin. Minimální počet dynamických výskytů.'],
            ['name' => 'dmax', 'label' => 'Dynamický spawn maximum', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 0, 'help' => 'Atribut dmax. Maximální počet dynamických výskytů.'],
        ];

        $this->pointTypeCatalog = [
            'vehicle' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::startsWith($event['name'], 'Vehicle')), 'cfgeventspawns.xml'), 'Tento formulář ukládá kandidátní pozici a natočení. Počet vozidel a jejich životnost řídí events.xml; attachmenty a náklad cfgspawnabletypes.xml.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'počet, limity, životnost a aktivace', 'cfgspawnabletypes.xml' => 'attachmenty, cargo a poškození']]),
            'heli' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::contains(Str::lower($event['name']), 'heli')), 'cfgeventspawns.xml'), 'Kandidátní pozice a natočení heli eventu. Jeho pravidla zůstávají v events.xml.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'počet, životnost, vzdálenosti a varianty']]),
            'convoy' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::contains(Str::lower($event['name']), ['convoy', 'train'])), 'cfgeventspawns.xml'), 'Pozice a natočení konvoje nebo vlaku. Složení objektů řídí cfgeventgroups.xml.', ['events.xml', 'cfgeventgroups.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'chování eventu', 'cfgeventgroups.xml' => 'objekty, relativní pozice, loot a natočení']]),
            'dynamic' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn () => true), 'cfgeventspawns.xml'), 'Kandidátní pozice a natočení vybraného eventu.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'všechny parametry chování eventu']]),
            'aerial' => array_merge($exact('cfgeventspawns.xml', $eventOptions($eventsBy(fn ($event) => Str::contains(Str::lower($event['name']), ['air', 'heli', 'plane'])), 'cfgeventspawns.xml'), 'Pozice a natočení leteckého eventu.', ['events.xml']), ['fields' => $eventFields, 'related' => ['events.xml' => 'počet, životnost, limity a varianty']]),
            'player' => array_merge($exact('cfgplayerspawnpoints.xml', [['value' => 'Nová spawn oblast', 'label' => 'Nová oblast generátoru hráče', 'target' => 'cfgplayerspawnpoints.xml', 'available' => isset($uploaded['cfgplayerspawnpoints.xml'])]], 'Přidává centrum oblasti generátoru, nikoli garantovaný přesný spawn. Formulář obsahuje všechny parametry spawn_params, generator_params i group_params pro zvolený režim.'), ['fields' => $playerFields, 'mode_defaults' => $playerModeDefaults, 'related' => ['cfgplayerspawnpoints.xml' => 'bod, skupina i kompletní nastavení zvoleného režimu fresh/hop/travel']]),
            'contaminated' => array_merge($exact('cfgeffectarea.json', [['value' => 'ContaminatedArea_Static', 'label' => 'Statická kontaminovaná zóna', 'target' => 'cfgeffectarea.json', 'available' => isset($uploaded['cfgeffectarea.json'])]], 'Zóna se uloží do pole Areas v cfgeffectarea.json včetně vertikálního rozsahu a částic.'), ['fields' => [
                ['name' => 'area_name', 'label' => 'Jedinečný název oblasti', 'type' => 'text', 'default' => 'Vlastni kontaminovana zona'],
                ['name' => 'radius', 'label' => 'Poloměr (m)', 'type' => 'number', 'min' => 1, 'max' => 5000, 'default' => 100],
                ['name' => 'pos_y', 'label' => 'Výška středu Y (m)', 'type' => 'number', 'min' => -1000, 'max' => 5000, 'step' => 0.1, 'default' => 0],
                ['name' => 'pos_height', 'label' => 'Dosah nad střed (m)', 'type' => 'number', 'min' => 0, 'max' => 5000, 'default' => 20],
                ['name' => 'neg_height', 'label' => 'Dosah pod střed (m)', 'type' => 'number', 'min' => 0, 'max' => 5000, 'default' => 3],
                ['name' => 'inner_part_dist', 'label' => 'Rozestup částic uvnitř (m)', 'type' => 'number', 'min' => 1, 'max' => 1000, 'default' => 80],
                ['name' => 'outer_offset', 'label' => 'Přesah vnějšího prstence (m)', 'type' => 'number', 'min' => 0, 'max' => 1000, 'default' => 30],
                ['name' => 'particle_name', 'label' => 'Hlavní částice', 'type' => 'text', 'default' => 'graphics/particles/contaminated_area_gas_bigass'],
                ['name' => 'around_particle', 'label' => 'Částice kolem hráče', 'type' => 'text', 'default' => 'graphics/particles/contaminated_area_gas_around'],
                ['name' => 'tiny_particle', 'label' => 'Jemná částice kolem hráče', 'type' => 'text', 'default' => 'graphics/particles/contaminated_area_gas_around_tiny'],
                ['name' => 'ppe_type', 'label' => 'PPE vizuální efekt', 'type' => 'text', 'default' => 'PPERequester_ContaminatedAreaTint'],
            ]]),
            'loot' => array_merge($exact('mapgrouppos.xml', $options($lootNames, 'mapgrouppos.xml'), 'Světová pozice existujícího prototypu skupiny se zapíše do mapgrouppos.xml.', ['mapgroupproto.xml']), ['fields' => [
                ['name' => 'name', 'label' => 'Classname budovy/skupiny', 'type' => 'text', 'list' => 'dz-mapgroup-name-catalog', 'autocomplete' => false, 'default' => '', 'help' => 'Napovídá známé prototypy z mapgroupproto.xml. Nezmění vizuální model ve hře (ten je daný terénem mapy) — přepíše jen to, podle jaké šablony (mapgroupproto.xml) se v tomto bodě generuje loot. Pokud se classname neshoduje se skutečnou budovou na pozici, loot body se nemusí trefit do modelu (spawn mimo/v zemi/vůbec).'],
                ['name' => 'pos_y', 'label' => 'Výška Y (m)', 'type' => 'number', 'min' => -1000, 'max' => 5000, 'step' => 0.001, 'default' => 0],
                ['name' => 'pitch', 'label' => 'Náklon pitch (°)', 'type' => 'number', 'min' => -360, 'max' => 360, 'step' => 0.001, 'default' => 0],
                ['name' => 'yaw', 'label' => 'Natočení yaw (°)', 'type' => 'number', 'min' => -360, 'max' => 360, 'step' => 0.001, 'default' => 0],
                ['name' => 'roll', 'label' => 'Náklon roll (°)', 'type' => 'number', 'min' => -360, 'max' => 360, 'step' => 0.001, 'default' => 0],
                ['name' => 'orientation', 'label' => 'Atribut a (°)', 'type' => 'number', 'min' => 0, 'max' => 359.999, 'step' => 0.001, 'default' => 0],
            ]]),
            'animal' => [
                'target' => null, 'target_label' => 'odpovídající *_territories.xml', 'available' => true,
                'missing' => [], 'upload_url' => $uploadUrl('wolf_territories.xml'), 'options' => $animalOptions,
                'help' => 'Vyberte druh. Nová zóna se uloží pouze do jeho vlastního *_territories.xml; events.xml se tím nemění.',
                'fields' => $territoryFields,
                'related' => ['events.xml' => 'počet stád, velikost stáda, lifetime a restock'],
            ],
            'territory' => [
                'target' => null, 'target_label' => '*_territories.xml', 'available' => count($territoryOptions) > 0,
                'missing' => count($territoryOptions) ? [] : ['*_territories.xml'], 'upload_url' => $uploadUrl('*_territories.xml'),
                'options' => $territoryOptions, 'help' => 'Obecná oblast se zapíše do konkrétního nahraného souboru teritorií.',
                'fields' => $territoryFields,
            ],
            'infected' => [
                'target' => null, 'target_label' => 'odpovídající *_territories.xml nakažených', 'available' => count($infectedOptions) > 0,
                'missing' => count($infectedOptions) ? [] : ['cfgenvironment.xml s registrací nakažených a jejich territories soubor'],
                'upload_url' => $uploadUrl('cfgenvironment.xml'), 'options' => $infectedOptions,
                'help' => 'Vyberte registraci nakažených. Zóna se uloží do jejího vlastního *_territories.xml; events.xml se tím nemění. Zadejte přesný název tieru (např. InfectedVillageTier1) do pole Úloha zóny.',
                'fields' => $territoryFields,
                'related' => ['events.xml' => 'počet skupin, lifetime a restock nakažených'],
            ],
            'custom' => [
                'target' => null, 'target_label' => 'Object Spawner JSON', 'available' => false,
                'missing' => ['Object Spawner JSON'], 'upload_url' => $uploadUrl('*spawner*.json'),
                'options' => [], 'help' => 'Vlastní objekt nelze bezpečně zapsat bez třídy objektu, orientace a aktuálního Object Spawner JSON.',
            ],
        ];
    }

    public function loadMarkers(): void
    {
        $this->markers = [];
        $this->markerCounts = [];
        $this->loadedSources = [];
        $this->layerScopes = [];
        $this->lootCategoryLegend = [];
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project) {
            return;
        }

        $reader = app(MapConfigurationReader::class);
        $revisions = $this->latestRevisions($project);
        $groupCategories = $this->groupPrototypeCategories($reader, $revisions);
        $this->lootCategoryLegend = $this->buildLootCategoryLegend($revisions, $groupCategories);

        foreach ($revisions as $revision) {
            $filename = $this->revisionFilename($revision);
            if (! Storage::disk('dayz')->exists($revision->storage_path)) {
                continue;
            }
            $content = Storage::disk('dayz')->get($revision->storage_path);
            $this->layerScopes[$filename] = $this->scopesFor($filename, $content);
            if ($filename === 'mapgrouppos.xml' && ! $this->showDenseLayers) {
                $this->markerCounts[$filename] = substr_count($content, '<group ');
                continue;
            }
            foreach ($reader->markers($filename, $content) as $marker) {
                $marker['revision_id'] = $revision->id;
                if ($filename === 'mapgrouppos.xml') {
                    $categories = $groupCategories[$marker['label']] ?? [];
                    $marker['categories'] = $categories;
                    $marker['color'] = $this->colorForCategories($categories);
                    if ($categories !== []) {
                        $marker['help'] .= ' Loot kategorie podle mapgroupproto.xml: '.implode(', ', $categories).'.';
                    }
                } else {
                    $marker['color'] = $this->colorFor($filename);
                }
                $this->markers[] = $marker;
                $this->markerCounts[$filename] = ($this->markerCounts[$filename] ?? 0) + 1;
                $this->loadedSources[$filename] = true;
            }
        }
    }

    /** Known zone `name` values for the "Úloha zóny" datalist: the 4 common animal zone types plus every zone name already used in the project's loaded territory files (ambient/infected use their own per-species or per-tier names). */
    public function zoneTypeCatalog(): array
    {
        return collect($this->markers)
            ->filter(fn (array $marker): bool => $marker['type'] === 'territory')
            ->map(fn (array $marker) => $marker['parameters']['zone_type'] ?? null)
            ->filter()
            ->merge(['HuntingGround', 'Rest', 'Graze', 'Water'])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<array{value:string,label:string,count:int}> */
    private function scopesFor(string $filename, string $content): array
    {
        $xml = @simplexml_load_string($content);
        if (! $xml) {
            return [];
        }
        $scopes = [];
        if ($filename === 'cfgeventspawns.xml') {
            foreach ($xml->event ?? [] as $event) {
                $count = count($event->pos ?? []);
                if ($count > 0) {
                    $name = (string) ($event['name'] ?? '');
                    $scopes[] = ['value' => 'event:'.$name, 'label' => $name.' · '.$this->eventSpawnCategory($name), 'count' => $count];
                }
            }
        }
        if ($filename === 'cfgplayerspawnpoints.xml') {
            foreach (['fresh', 'hop', 'travel'] as $mode) {
                $modeNode = $xml->{$mode};
                if (! $modeNode) {
                    continue;
                }
                $modeCount = 0;
                foreach ($modeNode->generator_posbubbles->group ?? [] as $group) {
                    $count = count($group->pos ?? []);
                    if ($count === 0) {
                        continue;
                    }
                    $name = (string) ($group['name'] ?? 'Bez názvu');
                    $modeCount += $count;
                    $scopes[] = ['value' => "group:{$mode}|{$name}", 'label' => strtoupper($mode).' · '.$name, 'count' => $count];
                }
                if ($modeCount > 0) {
                    array_unshift($scopes, ['value' => 'mode:'.$mode, 'label' => strtoupper($mode).' · celý režim', 'count' => $modeCount]);
                }
            }
        }

        return $scopes;
    }

    /** cfgeventspawns.xml has no fresh/hop/travel concept (that's specific to player spawns) — classify by name instead. */
    private function eventSpawnCategory(string $name): string
    {
        $lower = Str::lower($name);

        return match (true) {
            Str::contains($lower, 'heli') => 'Heli crash',
            Str::contains($lower, ['convoy', 'train']) => 'Konvoj/vlak',
            Str::startsWith($name, 'Vehicle') => 'Vozidlo',
            Str::contains($lower, ['air', 'plane']) => 'Letecký event',
            default => 'Ostatní event',
        };
    }

    private function projectQuery()
    {
        return Project::query()->when(! auth()->user()?->is_admin, fn ($query) => $query->where('user_id', auth()->id()));
    }

    private function latestRevisions(?Project $project)
    {
        return $project?->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->unique(fn ($revision) => $this->revisionFilename($revision))
            ->values() ?? collect();
    }

    private function revisionFilename($revision): string
    {
        return strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path)));
    }

    private function source(string $filename, string $description, bool $plottable, $revision = null): array
    {
        return [
            'filename' => $filename,
            'description' => $description,
            'plottable' => $plottable,
            'uploaded' => $revision !== null,
            'revision_id' => $revision?->id,
            'revision_number' => $revision?->revision_number,
            'undeployed' => $revision !== null && $revision->downloaded_at === null,
            'marker_count' => $this->markerCounts[$filename] ?? 0,
            'loaded' => (bool) ($this->loadedSources[$filename] ?? false),
            'color' => $this->colorFor($filename),
            'dot_style' => $this->dotStyleFor($filename),
        ];
    }

    /**
     * mapgrouppos.xml markers are colored per loot category (see loadMarkers()), not one flat
     * color per file — a single-color dot there would lie about what's actually on the map, so
     * show a small pie of the known category colors instead once we have that data.
     */
    private function dotStyleFor(string $filename): string
    {
        if ($filename === 'mapgrouppos.xml' && count($this->lootCategoryLegend)) {
            $colors = array_column($this->lootCategoryLegend, 'color');
            $slice = 100 / count($colors);
            $stops = [];
            foreach ($colors as $index => $color) {
                $stops[] = $color.' '.($index * $slice).'% '.(($index + 1) * $slice).'%';
            }

            return 'background:conic-gradient('.implode(', ', $stops).')';
        }

        return 'background:'.$this->colorFor($filename);
    }

    private function colorFor(string $filename): string
    {
        $colors = ['#b8ed55', '#80b8ff', '#f1b44c', '#e96a5f', '#d58cff', '#55e0c1', '#ff82b2', '#f6d365'];

        return $colors[abs(crc32(strtolower($filename))) % count($colors)];
    }

    /** @return array<string, list<string>> group name => categories, read from mapgroupproto.xml if uploaded. */
    private function groupPrototypeCategories(MapConfigurationReader $reader, $revisions): array
    {
        $revision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'mapgroupproto.xml');
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            return [];
        }

        return $reader->groupPrototypeCategories(Storage::disk('dayz')->get($revision->storage_path));
    }

    /**
     * How many types.xml items exist per category, so the map legend can show e.g.
     * "weapons · 12 položek" next to the color swatch instead of just a color key.
     *
     * @param  array<string, list<string>>  $groupCategories
     * @return list<array{category:string, color:string, item_count:int}>
     */
    private function buildLootCategoryLegend($revisions, array $groupCategories): array
    {
        if ($groupCategories === []) {
            return [];
        }
        $usedCategories = array_values(array_unique(array_merge(...array_values($groupCategories))));
        sort($usedCategories);

        $itemCounts = [];
        $typesRevision = $revisions->first(fn ($item) => $this->revisionFilename($item) === 'types.xml');
        if ($typesRevision && Storage::disk('dayz')->exists($typesRevision->storage_path)) {
            $entries = app(TypesXmlEditor::class)->entries(Storage::disk('dayz')->get($typesRevision->storage_path));
            foreach ($entries as $entry) {
                $category = strtolower((string) ($entry['category'] ?: 'other'));
                $itemCounts[$category] = ($itemCounts[$category] ?? 0) + 1;
            }
        }

        return array_map(fn (string $category): array => [
            'category' => $category,
            'color' => self::CATEGORY_COLORS[strtolower($category)] ?? '#9aa99b',
            'item_count' => $itemCounts[strtolower($category)] ?? 0,
        ], $usedCategories);
    }

    /** @param list<string> $categories */
    private function colorForCategories(array $categories): string
    {
        if ($categories === []) {
            return '#6b7a6d';
        }
        $lowered = array_map('strtolower', $categories);
        foreach (self::CATEGORY_PRIORITY as $priority) {
            if (in_array($priority, $lowered, true)) {
                return self::CATEGORY_COLORS[$priority];
            }
        }

        return self::CATEGORY_COLORS[$lowered[0]] ?? '#9aa99b';
    }

    public function importMapConfiguration(ConfigurationImporter $importer): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project || ! $this->mapFile) {
            return;
        }
        $importer->import($project, $this->mapFile, auth()->user());
        $this->reset('mapFile');
        $this->loadMarkers();
        $this->loadMapSources();
        $this->loadEventCatalog();
        $this->loadSpawnPointWarnings();
        $this->loadEventSpawnWarnings();
    }

    /**
     * Short "what can I add here" legend shown by default on the map — pulls its description
     * text straight from pointTypeCatalog()'s own `help` strings instead of duplicating them,
     * so the legend can't drift out of sync with what the "Přidat bod" modal actually does.
     *
     * @return list<array{key:string,label:string,help:string}>
     */
    public function mapCategoryIntro(): array
    {
        $labels = [
            'animal' => 'Zvířata',
            'infected' => 'Nakažení',
            'loot' => 'Loot',
            'dynamic' => 'Eventy a vozidla',
            'player' => 'Spawn hráčů',
            'contaminated' => 'Kontaminace',
            'territory' => 'Území / základny',
        ];

        return collect($labels)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
                'help' => explode('.', (string) ($this->pointTypeCatalog[$key]['help'] ?? ''))[0] ?? '',
            ])
            ->values()
            ->all();
    }

    /** Pushes a single map layer's current (undeployed) revision straight to the live server over FTP — same action EditConfiguration offers per file, surfaced here so a changed layer can go live without leaving the map. */
    public function pushSourceToFtp(int $revisionId, FtpBrowser $browser, ServerFileLayout $layout): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project || ! $project->hasFtpConnection()) {
            return;
        }

        $revision = $project->revisions()->with('configurationImport')->find($revisionId);
        if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
            Notification::make()->danger()->title('Revize nebyla nalezena.')->send();

            return;
        }

        try {
            $filename = $this->pushRevisionToFtp($project, $revision, $browser, $layout);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title('Nahrání na server selhalo')->body($exception->getMessage())->send();

            return;
        }

        $this->loadMapSources();
        Notification::make()->success()->title('Nahráno na server')->body($filename)->send();
    }

    /** Pushes every currently undeployed map layer (changed here but never pushed/downloaded) to the live server over FTP in one go. */
    public function pushAllUndeployedToFtp(FtpBrowser $browser, ServerFileLayout $layout): void
    {
        $project = $this->projectId ? $this->projectQuery()->find($this->projectId) : null;
        if (! $project || ! $project->hasFtpConnection()) {
            return;
        }

        $revisionIds = collect($this->mapSources)
            ->filter(fn (array $source): bool => $source['uploaded'] && $source['undeployed'])
            ->pluck('revision_id');
        if ($revisionIds->isEmpty()) {
            return;
        }

        $uploaded = [];
        $failed = [];
        foreach ($revisionIds as $revisionId) {
            $revision = $project->revisions()->with('configurationImport')->find($revisionId);
            if (! $revision || ! Storage::disk('dayz')->exists($revision->storage_path)) {
                continue;
            }
            try {
                $uploaded[] = $this->pushRevisionToFtp($project, $revision, $browser, $layout);
            } catch (RuntimeException $exception) {
                $failed[] = ($revision->configurationImport?->original_filename ?? basename($revision->storage_path)).': '.$exception->getMessage();
            }
        }

        $this->loadMapSources();
        if ($uploaded !== []) {
            Notification::make()->success()->title(count($uploaded).'× nahráno na server')->body(implode(', ', $uploaded))->send();
        }
        if ($failed !== []) {
            Notification::make()->danger()->title(count($failed).'× se nepodařilo nahrát')->body(implode(' | ', $failed))->send();
        }
    }

    /** @throws RuntimeException */
    private function pushRevisionToFtp(Project $project, ConfigurationRevision $revision, FtpBrowser $browser, ServerFileLayout $layout): string
    {
        $filename = $revision->configurationImport?->original_filename ?? basename($revision->storage_path);
        $path = $layout->relativePath($filename, $project);
        $content = Storage::disk('dayz')->get($revision->storage_path);
        $browser->write($project, $path, $content);
        $remote = $browser->read($project, $path);
        if (hash('sha256', $content) !== hash('sha256', $remote)) {
            throw new RuntimeException("Server po zápisu vrátil jiný obsah souboru ({$path}).");
        }
        $revision->forceFill(['downloaded_at' => now()])->save();

        return $filename;
    }

    public function getTitle(): string
    {
        return 'Mapa · '.$this->map;
    }

    public function getSubheading(): ?string
    {
        return 'Souřadnice, vrstvy a bezpečné revize mapových konfiguračních souborů.';
    }
}
