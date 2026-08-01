<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\ConfigurationRevision;
use App\Models\LogAnalysis;
use App\Services\Import\ConfigurationImporter;
use App\Services\PlatformDetection\PlatformCompatibility;
use App\Services\PlatformDetection\PlatformDetector;
use App\Services\Revision\ConfigurationRevisionEditor;
use App\Services\Revision\EnvironmentXmlEditor;
use App\Services\Revision\EventGroupsXmlEditor;
use App\Services\Revision\JsonConfigurationEditor;
use App\Services\Revision\ServerConfigEditor;
use App\Services\Revision\TypesXmlEditor;
use App\Services\Dayz\EventsXmlEditor;
use App\Services\Revision\WeatherXmlEditor;
use App\Services\Revision\XmlConfigurationEditor;
use App\Services\Dayz\ConfigurationFieldMetadata;
use App\Services\Dayz\ServerFileLayout;
use App\Services\Ftp\FtpBrowser;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use RuntimeException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class EditConfiguration extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected static string $view = 'filament.resources.project-resource.pages.edit-configuration';

    public int $revisionId;

    #[Locked]
    public int $revisionNumber;

    public string $mode = 'visual';

    public string $rawContent = '';

    public string $changeSummary = '';

    public string $currentFilename = '';

    public string $search = '';

    public ?string $selectedType = null;

    /** @var array<string, mixed> */
    public array $typeForm = [];

    /** @var list<array<string, mixed>> */
    public array $typeEntries = [];

    public string $eventSearch = '';

    public ?string $selectedEvent = null;

    /** @var array<string, mixed> */
    public array $eventForm = [];

    /** @var list<array<string, mixed>> */
    public array $eventEntries = [];

    /** @var list<array<string, mixed>> */
    public bool $visualSupported = false;

    public ?string $visualKind = null;

    /** @var array<string, mixed> */
    public array $weatherForm = [];

    public array $serverConfig = [];
    public array $whitelistEntries = [];
    public string $newWhitelistId = '';
    public string $newWhitelistComment = '';
    public array $banEntries = [];
    public string $newBanId = '';
    public string $newBanComment = '';
    public array $priorityEntries = [];
    public string $newPriorityId = '';
    public string $newPriorityComment = '';
    public array $messagesEntries = [];

    public array $jsonFields = [];

    public array $jsonValues = [];

    public array $xmlFields = [];

    public array $xmlValues = [];

    public array $eventGroups = [];
    public array $eventSpawns = [];
    public string $eventSpawnSearch = '';
    public ?string $expandedEventSpawn = null;

    public array $newEventChildTypes = [];

    public string $environmentSearch = '';

    public ?string $selectedEnvironmentTerritory = null;

    /** @var array<string, mixed> */
    public array $environmentForm = [];

    /** @var list<array<string, mixed>> */
    public array $environmentEntries = [];

    public bool $showAddAnimalForm = false;

    /** @var array<string, mixed> */
    public array $newAnimalForm = [
        'name' => '',
        'type' => 'Herd',
        'behavior' => '',
        'file_mode' => 'existing',
        'file' => '',
        'new_file' => '',
        'agents' => [],
        'items' => [],
    ];

    public bool $showAddForm = false;

    public bool $showClassPicker = false;

    public string $classPickerCategory = 'weapons';

    public string $classPickerSearch = '';

    public int $classPickerLimit = 60;

    /** @var array<string, mixed> */
    public array $newTypeForm = [
        'name' => '',
        'category' => 'tools',
        'usages' => [],
        'nominal' => 10,
        'lifetime' => 14400,
        'restock' => 1800,
        'min' => 5,
        'quantmin' => -1,
        'quantmax' => -1,
        'cost' => 100,
    ];

    public string $detectedPlatform = 'unknown';

    /** @var list<string> */
    public array $platformReasons = [];

    /** @var list<string> */
    public array $platformWarnings = [];
    public array $dependencyWarnings = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if (! $this->getRecord()->revisions()->exists()) {
            $this->redirect(\App\Filament\Pages\ConfigurationWizard::getUrl(['project' => $this->getRecord()->id]));

            return;
        }

        $requestedRevision = request()->integer('revision');
        $requestedType = trim((string) request()->string('type'));
        $requestedEvent = trim((string) request()->string('event'));
        $requestedTerritory = trim((string) request()->string('register_territory'));

        $requested = $requestedRevision
            ? $this->getRecord()->revisions()->whereKey($requestedRevision)->first()
            : null;
        if (! $requested && $requestedType !== '') {
            $requested = $this->latestRevisionByFilename('types.xml');
        }
        if (! $requested && $requestedEvent !== '') {
            $requested = $this->latestRevisionByFilename('events.xml');
        }

        if ($requested) {
            $this->loadRevision($requested);
        } else {
            $this->loadLatestRevision();
        }

        if ($requestedType !== '') {
            $this->selectType($requestedType);
        }
        if ($requestedEvent !== '') {
            $this->selectEvent($requestedEvent, app(EventsXmlEditor::class));
        }
        if ($requestedTerritory !== '') {
            $this->prepareTerritoryRegistration($requestedTerritory);
        }
    }

    private function prepareTerritoryRegistration(string $filename): void
    {
        $filename = basename(str_replace('\\', '/', $filename));
        if (! str_ends_with(strtolower($filename), '_territories.xml')) {
            return;
        }

        $base = preg_replace('/_territories\.xml$/i', '', $filename) ?: $filename;
        $suggestedName = Str::studly(str_replace(['-', '_'], ' ', $base));
        $this->newAnimalForm = [
            'name' => $suggestedName,
            'type' => 'Herd',
            'behavior' => 'DZdomesticGroupBeh',
            'file_mode' => 'existing',
            'file' => $filename,
            'new_file' => '',
            'agents' => [],
            'items' => [],
        ];
        $this->selectedEnvironmentTerritory = null;
        $this->showAddAnimalForm = true;
        Notification::make()
            ->warning()
            ->title('Průvodce registrací territory souboru')
            ->body("Soubor {$filename} je nahraný, ale hra ho bez této registrace nepoužije. Zkontrolujte navržený druh a behavior a potom uložte.")
            ->send();
    }

    private function latestRevisionByFilename(string $filename): ?ConfigurationRevision
    {
        return $this->getRecord()->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->first(fn (ConfigurationRevision $revision): bool => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))) === $filename);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Editor · '.$this->getRecord()->name;
    }

    public function getHeading(): string|Htmlable
    {
        $platform = match ($this->getRecord()->platform) {
            'playstation' => 'PlayStation',
            'xbox' => 'Xbox',
            'steam' => 'PC / Steam',
            default => 'Neznámá platforma',
        };
        $selectedClass = $this->getRecord()->platform === 'steam' ? 'dz-heading-badge pc' : 'dz-heading-badge safe';
        $detected = $this->detectedPlatform === 'steam' && $this->getRecord()->platform !== 'steam'
            ? '<span class="dz-heading-badge pc">Detekováno PC-only</span>'
            : '';

        return new HtmlString(
            '<span class="dz-heading">'.e($this->getTitle()).'</span> '
            .'<span class="'.$selectedClass.'">'.e($platform).'</span> '
            .$detected,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [ProjectResource::getUrl() => 'Servery'];
    }

    public function configurationDescription(): string
    {
        return $this->descriptionForFilename($this->currentFilename);
    }

    public function xmlFieldDescription(array $field): string
    {
        return app(ConfigurationFieldMetadata::class)->xml($this->currentFilename, $field);
    }

    public function serverFieldDescription(string $key): string
    {
        return app(ConfigurationFieldMetadata::class)->server($key);
    }

    public function jsonFieldDescription(array $field): string
    {
        return app(ConfigurationFieldMetadata::class)->json($field);
    }

    public function descriptionForFilename(string $filename): string
    {
        if (str_ends_with(strtolower($filename), '_territories.xml')) {
            return 'Teritoria zvířat; souřadnice a hustota výskytu konkrétního druhu na mapě.';
        }
        if (str_starts_with(strtolower($filename), 'dayzps-settings-')) {
            return 'Export nastavení z Nitrado ovládacího panelu (specifický formát, ne přímo serverDZ.cfg). Bezpečně uložen jako revize; pole níže jsou zobrazená obecně.';
        }

        return match (strtolower($filename)) {
            'serverdz.cfg' => 'Hlavní nastavení serveru: přístup, hráči, čas, síť, logování a persistence.',
            'whitelist.txt' => 'Seznam povolených hráčských UID; aktivuje se volbou enableWhitelist v serverDZ.cfg.',
            'ban.txt' => 'Seznam zablokovaných hráčských UID, které se nesmí připojit na server.',
            'priority.txt' => 'Seznam hráčských UID s přednostním místem v přihlašovací frontě.',
            'dayzsettings.xml' => 'Nastavení job systému serveru: maximální a rezervovaná CPU jádra a velikosti front.',
            'beserver_x64.cfg' => 'BattlEye RCon konfigurace; obsahuje RConPassword a omezení vzdálené administrace.',
            'cfglimitsdefinition.xml' => 'Kategorie a usage flagy používané ekonomikou při výběru lootů.',
            'globals.xml' => 'Globální limity zvířat, infikovaných, loot economy a cleanup serveru.',
            'events.xml' => 'Počty, minima, maxima a životnost dynamických eventů jako zombie, loot nebo heli crash.',
            'cfgeventspawns.xml' => 'Souřadnice a orientace pevných eventů na mapě.',
            'cfgspawnabletypes.xml' => 'Obsah kontejnerů, cargo a attachmenty, které se mohou spawnout uvnitř předmětu.',
            'cfglimitsdefinition.xml' => 'Kategorie, usage flagy a definice limitů pro ekonomiku.',
            'mapgrouppos.xml' => 'Pozice skupin budov a loot zón na mapě.',
            'economycore.xml' => 'Základní chování dynamické ekonomiky a respawnu.',
            'cfgeconomycore.xml' => 'Přepínače a vazby ekonomiky mezi economy, events a types.',
            'cfgeventgroups.xml' => 'Skupiny eventů a jejich společné spawnování.',
            'cfgignorelist.xml' => 'Seznam tříd a objektů, které má ekonomika ignorovat.',
            'cfglimitsdefinitionuser.xml' => 'Vlastní definice kategorií a usage flagů serveru.',
            'cfgplayerspawnpoints.xml' => 'Pevné body pro spawn hráčů na mapě.',
            'cfgrandompresets.xml' => 'Přednastavené náhodné konfigurace eventů a ekonomiky.',
            'cfgeffectarea.json' => 'Efektové oblasti, kontaminace a jejich parametry.',
            'cfgundergroundtriggers.json' => 'Spouštěče podzemních oblastí a jejich aktivace.',
            'mapclusterproto.xml' => 'Prototypy mapových clusterů budov.',
            'mapgroupproto.xml' => 'Prototypy skupin budov a loot skupin.',
            'mapgroupcluster.xml' => 'Umístění clusterů budov na mapě.',
            'mapgroupcluster01.xml' => 'Umístění clusterů budov na mapě (část 1).',
            'mapgroupcluster02.xml' => 'Umístění clusterů budov na mapě (část 2).',
            'mapgroupcluster03.xml' => 'Umístění clusterů budov na mapě (část 3).',
            'mapgroupcluster04.xml' => 'Umístění clusterů budov na mapě (část 4).',
            'mapgroupdirt.xml' => 'Doplňková data mapových skupin.',
            'messages.xml' => 'Automatické serverové zprávy, intervaly a jejich životnost.',
            'spawnerdata.json' => 'Object Spawner: objekty, jejich pozice, orientace, měřítko a CE persistence.',
            'init.c' => 'Inicializační skript mise. Pokročilá PC konfigurace vyžadující Enforce Script.',
            'cfggameplay.json' => 'Gameplay nastavení: stamina, damage, respawn, UI, svět a pohyb hráče.',
            'cfgenvironment.xml' => 'Zvířata a nakažení: chování, napojený soubor teritorií, spawnované třídy a počty.',
            'cfgweather.xml' => 'Počáteční a mezní hodnoty počasí: oblačnost, mlha, déšť, sníh, vítr a bouřky.',
            'cfgplayerspawnpoints.xml' => 'Spawnovací body hráčů a jejich orientace.',
            'territory-type' => 'Teritoria zvířat; souřadnice a hustota výskytu druhu.',
            default => 'Pokročilá konfigurace serveru. Před uložením se ověří syntaxe XML nebo JSON.',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadConfiguration')
                ->label('Nahrát novou konfiguraci')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn (): string => url('/admin/configuration-import?project='.$this->getRecord()->id)),
            Actions\Action::make('downloadConfiguration')
                ->label(fn (): string => in_array($this->revisionId, $this->undeployedRevisionIds(), true)
                    ? 'Stáhnout do počítače (nestaženo)'
                    : 'Stáhnout do počítače')
                ->icon('heroicon-o-arrow-down-tray')
                ->color(fn (): string => in_array($this->revisionId, $this->undeployedRevisionIds(), true) ? 'danger' : 'gray')
                ->url(fn (): string => route('configuration-revision.download', ['project' => $this->getRecord()->id, 'revision' => $this->revisionId])),
            Actions\Action::make('downloadAllConfiguration')
                ->label('Stáhnout vše (ZIP)')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('gray')
                ->url(fn (): string => route('project.configuration.download-all', ['project' => $this->getRecord()->id])),
            Actions\Action::make('pushToFtp')
                ->label('Nahrát na server (FTP)')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->visible(fn (): bool => $this->getRecord()->hasFtpConnection())
                ->requiresConfirmation()
                ->modalHeading('Nahrát tuto revizi přímo na server?')
                ->modalDescription(fn (): string => "Přepíše soubor na živém serveru na cestě „{$this->ftpTargetPath()}“ obsahem aktuální revize ({$this->currentFilename} · revize #{$this->revisionNumber}). Původní soubor na serveru se dá vrátit jen ručním nahráním starší revize.")
                ->modalSubmitActionLabel('Nahrát na server')
                ->action('pushToFtp'),
        ];
    }

    public function ftpTargetPath(): string
    {
        return app(ServerFileLayout::class)->relativePath($this->currentFilename, $this->getRecord());
    }

    public function pushToFtp(FtpBrowser $browser): void
    {
        $project = $this->getRecord();
        if (! $project->hasFtpConnection()) {
            return;
        }

        $path = $this->ftpTargetPath();

        try {
            $browser->write($project, $path, $this->rawContent);
        } catch (RuntimeException $exception) {
            Notification::make()->danger()->title('Nahrání na server selhalo')->body($exception->getMessage())->send();

            return;
        }

        $this->sourceRevision()->forceFill(['downloaded_at' => now()])->save();

        Notification::make()
            ->success()
            ->title('Nahráno na server')
            ->body("{$this->currentFilename} → {$path}")
            ->send();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filteredTypes(): array
    {
        if ($this->search === '') {
            return array_slice($this->typeEntries, 0, 200);
        }

        $search = mb_strtolower($this->search);

        return array_slice(array_values(array_filter(
            $this->typeEntries,
            static fn (array $entry): bool => str_contains(mb_strtolower($entry['name']), $search),
        )), 0, 200);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function groupedTypes(): array
    {
        $groups = [];
        foreach ($this->filteredTypes() as $entry) {
            $category = $entry['category'] ?: 'other';
            $groups[$category][] = $entry;
        }

        ksort($groups);

        return $groups;
    }

    public function updatedNewTypeFormName(string $name): void
    {
        $name = mb_strtolower($name);
        $this->newTypeForm['category'] = match (true) {
            str_contains($name, 'ammo') || str_contains($name, 'akm') || str_contains($name, 'm4') || str_contains($name, 'rifle') => 'weapons',
            str_contains($name, 'bandage') || str_contains($name, 'morphine') || str_contains($name, 'epinephrine') || str_contains($name, 'antibiotic') => 'medical',
            str_contains($name, 'can') || str_contains($name, 'rice') || str_contains($name, 'water') => 'food',
            str_contains($name, 'shirt') || str_contains($name, 'hoodie') || str_contains($name, 'helmet') || str_contains($name, 'vest') => 'clothes',
            str_contains($name, 'car') || str_contains($name, 'olga') || str_contains($name, 'hatchback') => 'vehicles',
            str_contains($name, 'barrel') || str_contains($name, 'chest') || str_contains($name, 'crate') => 'containers',
            default => $this->newTypeForm['category'],
        };
    }

    /**
     * @return array<int, string>
     */
    public function editableFiles(): array
    {
        return $this->getRecord()->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->unique(fn (ConfigurationRevision $revision): string => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
            ->sortBy(fn (ConfigurationRevision $revision): string => strtolower($revision->configurationImport?->original_filename ?? basename($revision->storage_path)))
            ->mapWithKeys(fn (ConfigurationRevision $revision): array => [
                $revision->id => ($revision->configurationImport?->original_filename ?? basename($revision->storage_path))
                    ." · revize #{$revision->revision_number}",
            ])
            ->all();
    }

    /**
     * IDs from editableFiles() whose revision has never been downloaded — i.e. edited
     * here but not yet pulled onto the actual game server.
     *
     * @return list<int>
     */
    public function undeployedRevisionIds(): array
    {
        return $this->getRecord()->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->unique(fn (ConfigurationRevision $revision): string => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
            ->filter(fn (ConfigurationRevision $revision): bool => $revision->downloaded_at === null)
            ->pluck('id')
            ->all();
    }

    /**
     * Findings from the most recently analyzed server log for this project that are still
     * relevant to the file currently open — and still unresolved (checked against the live
     * file content, not just the frozen log snapshot), so a finding disappears once fixed.
     *
     * @return list<array{title: string, target: ?string, kind: string}>
     */
    public function relatedLogFindings(): array
    {
        $relevantKinds = match ($this->visualKind) {
            'types' => ['type-does-not-exist', 'type-not-spawnable'],
            'events' => ['missing-event-definition'],
            default => [],
        };
        if ($relevantKinds === []) {
            return [];
        }

        $analysis = LogAnalysis::query()
            ->where('project_id', $this->getRecord()->id)
            ->latest()
            ->first();
        if (! $analysis) {
            return [];
        }

        $liveTypeNames = $this->visualKind === 'types'
            ? collect($this->typeEntries)->pluck('name')->all()
            : [];
        $liveEventNames = $this->visualKind === 'events'
            ? array_map('strtolower', collect($this->eventEntries)->pluck('name')->all())
            : [];

        return collect($analysis->findings)
            ->filter(fn (array $finding): bool => in_array($finding['kind'] ?? null, $relevantKinds, true))
            ->filter(function (array $finding) use ($liveTypeNames, $liveEventNames): bool {
                $target = $finding['target'] ?? null;
                if ($target === null) {
                    return false;
                }

                return match ($this->visualKind) {
                    'types' => in_array($target, $liveTypeNames, true),
                    'events' => ! in_array(strtolower($target), $liveEventNames, true),
                    default => false,
                };
            })
            ->map(fn (array $finding): array => [
                'title' => $finding['title'],
                'target' => $finding['target'],
                'kind' => $finding['kind'],
            ])
            ->unique('target')
            ->values()
            ->all();
    }

    public function switchRevision(): void
    {
        $revision = $this->getRecord()->revisions()
            ->with('configurationImport')
            ->findOrFail($this->revisionId);
        $this->selectedType = null;
        $this->selectedEvent = null;
        $this->expandedEventSpawn = null;
        $this->showAddForm = false;
        $this->selectedEnvironmentTerritory = null;
        $this->showAddAnimalForm = false;
        $this->loadRevision($revision);
    }

    public function selectRevision(int $revisionId): void
    {
        $this->revisionId = $revisionId;
        $this->switchRevision();
    }

    public function selectType(string $name): void
    {
        $entry = collect($this->typeEntries)->firstWhere('name', $name);
        if (! $entry) {
            return;
        }

        $this->selectedType = $name;
        $this->typeForm = collect($entry)->only([
            'nominal',
            'lifetime',
            'restock',
            'min',
            'quantmin',
            'quantmax',
            'cost',
            'category',
            'count_in_cargo',
            'count_in_hoarder',
            'count_in_map',
            'count_in_player',
            'crafted',
            'deloot',
        ])->all();
        $this->typeForm['category'] = (string) ($entry['category'] ?? 'other');
        $this->typeForm['usages_csv'] = implode(', ', $entry['usages'] ?? []);
        $this->typeForm['tags_csv'] = implode(', ', $entry['tags'] ?? []);
        $this->typeForm['values_csv'] = implode(', ', $entry['values'] ?? []);
        $this->showAddForm = false;
    }

    /** @return list<array<string, mixed>> */
    public function filteredEvents(): array
    {
        if ($this->eventSearch === '') {
            return array_slice($this->eventEntries, 0, 200);
        }
        $search = mb_strtolower($this->eventSearch);

        return array_slice(array_values(array_filter(
            $this->eventEntries,
            static fn (array $entry): bool => str_contains(mb_strtolower($entry['name']), $search),
        )), 0, 200);
    }

    public function selectEvent(string $name, EventsXmlEditor $editor): void
    {
        if (! collect($this->eventEntries)->firstWhere('name', $name)) {
            return;
        }
        $this->selectedEvent = $name;
        $this->eventForm = $editor->values($this->rawContent, $name);
    }

    public function saveEvent(EventsXmlEditor $editor, ConfigurationRevisionEditor $revisionEditor): void
    {
        if (! $this->selectedEvent) {
            return;
        }
        $validated = $this->validate([
            'eventForm.nominal' => ['required', 'integer', 'min:0', 'max:100000'],
            'eventForm.min' => ['required', 'integer', 'min:0', 'max:100000'],
            'eventForm.max' => ['required', 'integer', 'min:0', 'max:100000'],
            'eventForm.lifetime' => ['required', 'integer', 'min:0', 'max:3888000'],
            'eventForm.restock' => ['required', 'integer', 'min:0', 'max:3888000'],
            'eventForm.saferadius' => ['required', 'integer', 'min:0', 'max:20000'],
            'eventForm.distanceradius' => ['required', 'integer', 'min:0', 'max:20000'],
            'eventForm.cleanupradius' => ['required', 'integer', 'min:0', 'max:20000'],
            'eventForm.deletable' => ['boolean'],
            'eventForm.init_random' => ['boolean'],
            'eventForm.remove_damaged' => ['boolean'],
            'eventForm.position' => ['required', 'in:fixed,player'],
            'eventForm.limit' => ['required', 'in:mixed,unlimited,nearest,farthest'],
            'eventForm.active' => ['boolean'],
            'eventForm.children' => ['array'],
            'eventForm.children.*.type' => ['required', 'string', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'eventForm.children.*.min' => ['required', 'integer', 'min:0', 'max:1000'],
            'eventForm.children.*.max' => ['required', 'integer', 'min:0', 'max:1000'],
            'eventForm.children.*.lootmin' => ['required', 'integer', 'min:0', 'max:1000'],
            'eventForm.children.*.lootmax' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $content = $editor->update($this->rawContent, $this->selectedEvent, $validated['eventForm']);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            "Upraven event {$this->selectedEvent}",
            auth()->user(),
        );
        $this->loadRevision($revision);
        $this->selectEvent($this->selectedEvent, $editor);

        Notification::make()->success()->title("Event {$this->selectedEvent} byl uložen")->body("Vznikla revize #{$revision->revision_number}.")->send();
    }

    public function addEventChild(): void
    {
        $this->eventForm['children'][] = ['type' => '', 'min' => 1, 'max' => 1, 'lootmin' => 0, 'lootmax' => 0];
    }

    public function removeEventChild(int $index): void
    {
        unset($this->eventForm['children'][$index]);
        $this->eventForm['children'] = array_values($this->eventForm['children'] ?? []);
    }

    /** @return list<array<string, mixed>> */
    public function filteredEnvironmentEntries(): array
    {
        if ($this->environmentSearch === '') {
            return array_slice($this->environmentEntries, 0, 200);
        }
        $search = mb_strtolower($this->environmentSearch);

        return array_slice(array_values(array_filter(
            $this->environmentEntries,
            static fn (array $entry): bool => str_contains(mb_strtolower($entry['name']), $search),
        )), 0, 200);
    }

    /** Currently uploaded *_territories.xml files, for the "napojený soubor" select. */
    public function territoryFileOptions(): array
    {
        return $this->getRecord()->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->map(fn (ConfigurationRevision $revision): string => strtolower(basename(str_replace(
                '\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path,
            ))))
            ->filter(fn (string $name): bool => Str::is('*_territories.xml', $name))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function selectEnvironmentTerritory(string $name, EnvironmentXmlEditor $editor): void
    {
        if (! collect($this->environmentEntries)->firstWhere('name', $name)) {
            return;
        }
        $this->selectedEnvironmentTerritory = $name;
        $this->environmentForm = $editor->values($this->rawContent, $name);
        $this->showAddAnimalForm = false;
    }

    public function saveEnvironmentTerritory(
        EnvironmentXmlEditor $editor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        if (! $this->visualSupported || ! $this->selectedEnvironmentTerritory) {
            return;
        }

        $validated = $this->validate($this->environmentValidationRules('environmentForm'))['environmentForm'];
        if (trim((string) ($validated['file'] ?? '')) !== '' && ! in_array(strtolower($validated['file']), $this->territoryFileOptions(), true)) {
            throw ValidationException::withMessages(['environmentForm.file' => 'Vyberte platný, už nahraný soubor teritorií.']);
        }

        try {
            $content = $editor->update($this->rawContent, $this->selectedEnvironmentTerritory, $validated);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['environmentForm.behavior' => $exception->getMessage()]);
        }
        $compatibility->assertEditable($this->getRecord(), $content, ['cfgenvironment.xml']);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            $this->changeSummary ?: "Upraveno zvíře {$this->selectedEnvironmentTerritory}",
            auth()->user(),
        );

        $name = $this->selectedEnvironmentTerritory;
        $this->loadRevision($revision);
        $this->selectEnvironmentTerritory($name, $editor);

        Notification::make()->success()->title("Zvíře {$name} bylo uloženo")->body("Vznikla revize #{$revision->revision_number}.")->send();
    }

    public function removeEnvironmentTerritory(EnvironmentXmlEditor $editor, ConfigurationRevisionEditor $revisionEditor): void
    {
        if (! $this->selectedEnvironmentTerritory) {
            return;
        }
        $name = $this->selectedEnvironmentTerritory;
        $content = $editor->removeTerritory($this->rawContent, $name);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            "Odebráno zvíře {$name}",
            auth()->user(),
        );
        $this->selectedEnvironmentTerritory = null;
        $this->loadRevision($revision);

        Notification::make()->success()->title("Zvíře {$name} bylo odebráno")->body("Vznikla revize #{$revision->revision_number}.")->send();
    }

    public function openAddAnimalForm(): void
    {
        $this->resetValidation();
        $this->selectedEnvironmentTerritory = null;
        $this->newAnimalForm = [
            'name' => '', 'type' => 'Herd', 'behavior' => '',
            'file_mode' => 'existing', 'file' => '', 'new_file' => '',
            'agents' => [], 'items' => [],
        ];
        $this->showAddAnimalForm = true;
    }

    public function closeAddAnimalForm(): void
    {
        $this->showAddAnimalForm = false;
    }

    public function addAnimalTerritory(
        EnvironmentXmlEditor $editor,
        ConfigurationImporter $importer,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        $rules = $this->environmentValidationRules('newAnimalForm');
        $rules['newAnimalForm.name'] = ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_.-]+$/'];
        $rules['newAnimalForm.file_mode'] = ['required', 'in:existing,new'];
        $rules['newAnimalForm.new_file'] = ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]*$/'];
        $validated = $this->validate($rules)['newAnimalForm'];

        if ($validated['file_mode'] === 'new') {
            $base = trim((string) $validated['new_file']);
            if ($base === '') {
                throw ValidationException::withMessages(['newAnimalForm.new_file' => 'Zadejte název nového souboru teritorií.']);
            }
            $filename = str_ends_with(strtolower($base), '_territories.xml') ? $base : $base.'_territories.xml';
            if (! $this->latestRevisionByFilename(strtolower($filename))) {
                $importer->importGeneratedFile(
                    $this->getRecord(),
                    $filename,
                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<territory-type>\n</territory-type>\n",
                    auth()->user(),
                );
            }
            $validated['file'] = $filename;
        } elseif (! in_array(strtolower((string) ($validated['file'] ?? '')), $this->territoryFileOptions(), true)) {
            throw ValidationException::withMessages(['newAnimalForm.file' => 'Vyberte platný, už nahraný soubor teritorií.']);
        }

        try {
            $content = $editor->addTerritory($this->rawContent, $validated);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['newAnimalForm.name' => $exception->getMessage()]);
        }
        $compatibility->assertEditable($this->getRecord(), $content, ['cfgenvironment.xml']);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            "Přidáno zvíře {$validated['name']}",
            auth()->user(),
        );

        $this->showAddAnimalForm = false;
        $this->loadRevision($revision);
        $this->selectEnvironmentTerritory($validated['name'], $editor);

        Notification::make()->success()->title("Zvíře {$validated['name']} bylo přidáno")->body("Vznikla revize #{$revision->revision_number}.")->send();
    }

    public function addEnvironmentAgent(string $target = 'environmentForm'): void
    {
        $this->{$target}['agents'][] = ['type' => 'Male', 'chance' => '', 'spawns' => [], 'items' => []];
    }

    public function removeEnvironmentAgent(int $index, string $target = 'environmentForm'): void
    {
        unset($this->{$target}['agents'][$index]);
        $this->{$target}['agents'] = array_values($this->{$target}['agents'] ?? []);
    }

    public function addEnvironmentAgentSpawn(int $agentIndex, string $target = 'environmentForm'): void
    {
        $this->{$target}['agents'][$agentIndex]['spawns'][] = ['configName' => '', 'chance' => ''];
    }

    public function removeEnvironmentAgentSpawn(int $agentIndex, int $spawnIndex, string $target = 'environmentForm'): void
    {
        unset($this->{$target}['agents'][$agentIndex]['spawns'][$spawnIndex]);
        $this->{$target}['agents'][$agentIndex]['spawns'] = array_values($this->{$target}['agents'][$agentIndex]['spawns'] ?? []);
    }

    public function addEnvironmentItem(string $target = 'environmentForm'): void
    {
        $this->{$target}['items'][] = ['name' => '', 'val' => 0];
    }

    public function removeEnvironmentItem(int $index, string $target = 'environmentForm'): void
    {
        unset($this->{$target}['items'][$index]);
        $this->{$target}['items'] = array_values($this->{$target}['items'] ?? []);
    }

    /** @return array<string, mixed> */
    private function environmentValidationRules(string $prefix): array
    {
        return [
            "{$prefix}.type" => ['required', 'in:Herd,Ambient'],
            "{$prefix}.behavior" => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_]+$/'],
            "{$prefix}.file" => ['nullable', 'string', 'max:160'],
            "{$prefix}.agents" => ['array'],
            "{$prefix}.agents.*.type" => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_]+$/'],
            "{$prefix}.agents.*.chance" => ['nullable', 'numeric', 'min:0', 'max:100000'],
            "{$prefix}.agents.*.spawns" => ['array'],
            "{$prefix}.agents.*.spawns.*.configName" => ['required', 'string', 'max:160', 'regex:/^[A-Za-z0-9_.-]+$/'],
            "{$prefix}.agents.*.spawns.*.chance" => ['nullable', 'numeric', 'min:0', 'max:100000'],
            "{$prefix}.agents.*.items" => ['array'],
            "{$prefix}.agents.*.items.*.name" => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_]+$/'],
            "{$prefix}.agents.*.items.*.val" => ['required', 'numeric', 'min:-1', 'max:2147483647'],
            "{$prefix}.items" => ['array'],
            "{$prefix}.items.*.name" => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_]+$/'],
            "{$prefix}.items.*.val" => ['required', 'numeric', 'min:-1', 'max:2147483647'],
        ];
    }

    public function openAddForm(): void
    {
        $this->resetValidation();
        $this->selectedType = null;
        $this->showAddForm = true;
    }

    public function openClassPicker(): void
    {
        $this->showClassPicker = true;
        $this->classPickerSearch = '';
        $this->classPickerLimit = 60;
    }

    public function closeClassPicker(): void
    {
        $this->showClassPicker = false;
    }

    public function chooseClass(string $name, string $category): void
    {
        $this->newTypeForm['name'] = $name;
        $this->newTypeForm['category'] = $category;
        $this->showClassPicker = false;
    }

    public function loadMoreCatalog(): void
    {
        $this->classPickerLimit += 60;
    }

    public function pickerEntries(): array
    {
        $entries = array_values(array_filter(
            $this->catalogEntries(),
            fn (array $entry): bool => ($entry['category'] ?: 'other') === $this->classPickerCategory,
        ));

        if ($this->classPickerSearch !== '') {
            $search = mb_strtolower($this->classPickerSearch);
            $entries = array_values(array_filter($entries, fn (array $entry): bool => str_contains(mb_strtolower($entry['name']), $search)));
        }

        return array_slice($entries, 0, $this->classPickerLimit);
    }

    public function pickerCategories(): array
    {
        return collect($this->catalogEntries())
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function catalogEntries(): array
    {
        return app(\App\Services\Dayz\ClassnameCatalog::class)->entries();
    }

    public function addType(
        TypesXmlEditor $typesEditor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        $validated = $this->validate([
            'newTypeForm.name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'newTypeForm.category' => ['required', 'in:weapons,food,medical,tools,clothes,containers,vehicles,other'],
            'newTypeForm.usages' => ['array'],
            'newTypeForm.usages.*' => ['in:Military,Hunting,Police,Medic,Town,Village,Farm,Industrial,Coast'],
            'newTypeForm.nominal' => ['required', 'integer', 'min:0', 'max:100000'],
            'newTypeForm.lifetime' => ['required', 'integer', 'min:0', 'max:3888000'],
            'newTypeForm.restock' => ['required', 'integer', 'min:0', 'max:3888000'],
            'newTypeForm.min' => ['required', 'integer', 'min:0', 'max:100000'],
            'newTypeForm.quantmin' => ['required', 'integer', 'min:-1', 'max:100'],
            'newTypeForm.quantmax' => ['required', 'integer', 'min:-1', 'max:100'],
            'newTypeForm.cost' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $name = $validated['newTypeForm']['name'];
        $values = collect($validated['newTypeForm'])->only([
            'nominal',
            'lifetime',
            'restock',
            'min',
            'quantmin',
            'quantmax',
            'cost',
        ])->map(static fn ($value): int => (int) $value)->all();

        $content = $typesEditor->add(
            $this->rawContent,
            $name,
            $values,
            $validated['newTypeForm']['category'],
            $validated['newTypeForm']['usages'] ?? [],
        );
        $compatibility->assertEditable($this->getRecord(), $content, ['types.xml']);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            "Přidána položka {$name}",
            auth()->user(),
        );

        $this->loadRevision($revision);
        $this->selectType($name);

        Notification::make()
            ->success()
            ->title("Položka {$name} byla přidána")
            ->body("Vznikla revize #{$revision->revision_number}.")
            ->send();
    }

    public function saveType(
        TypesXmlEditor $typesEditor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        if (! $this->visualSupported || ! $this->selectedType) {
            return;
        }

        $validated = $this->validate([
            'typeForm.nominal' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'typeForm.lifetime' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'typeForm.restock' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'typeForm.min' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'typeForm.quantmin' => ['required', 'integer', 'min:-1', 'max:100'],
            'typeForm.quantmax' => ['required', 'integer', 'min:-1', 'max:100'],
            'typeForm.cost' => ['required', 'integer', 'min:0', 'max:1000'],
            'typeForm.category' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'typeForm.usages_csv' => ['nullable', 'string', 'max:1000'],
            'typeForm.tags_csv' => ['nullable', 'string', 'max:1000'],
            'typeForm.values_csv' => ['nullable', 'string', 'max:1000'],
            'typeForm.count_in_cargo' => ['required', 'integer', 'in:0,1'],
            'typeForm.count_in_hoarder' => ['required', 'integer', 'in:0,1'],
            'typeForm.count_in_map' => ['required', 'integer', 'in:0,1'],
            'typeForm.count_in_player' => ['required', 'integer', 'in:0,1'],
            'typeForm.crafted' => ['required', 'integer', 'in:0,1'],
            'typeForm.deloot' => ['required', 'integer', 'in:0,1'],
            'changeSummary' => ['nullable', 'string', 'max:500'],
        ]);

        $values = $validated['typeForm'];
        if ((int) $values['min'] > (int) $values['nominal']) {
            throw ValidationException::withMessages([
                'typeForm.min' => 'Minimum nesmí být vyšší než cílové množství.',
            ]);
        }
        if ((int) $values['quantmin'] !== -1
            && (int) $values['quantmax'] !== -1
            && (int) $values['quantmin'] > (int) $values['quantmax']) {
            throw ValidationException::withMessages([
                'typeForm.quantmax' => 'Maximální naplnění nesmí být nižší než minimální naplnění.',
            ]);
        }
        foreach (['usages', 'tags', 'values'] as $key) {
            $values[$key] = $this->commaSeparatedValues(
                (string) ($values[$key.'_csv'] ?? ''),
                "typeForm.{$key}_csv",
            );
            unset($values[$key.'_csv']);
        }
        $content = $typesEditor->update($this->rawContent, $this->selectedType, $values);
        $compatibility->assertEditable($this->getRecord(), $content, ['types.xml']);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            $validated['changeSummary'] ?: "Vizuální úprava {$this->selectedType}",
            auth()->user(),
        );

        $this->loadRevision($revision);
        $this->selectType($this->selectedType);

        Notification::make()
            ->success()
            ->title("Revize #{$revision->revision_number} uložena")
            ->body("Položka {$this->selectedType} byla upravena.")
            ->send();
    }

    public function removeType(
        TypesXmlEditor $typesEditor,
        ConfigurationRevisionEditor $revisionEditor,
    ): void {
        if (! $this->visualSupported || ! $this->selectedType) {
            return;
        }

        $this->performTypeRemoval($this->selectedType, $typesEditor, $revisionEditor);
        $this->selectedType = null;
    }

    /** Same as removeType(), but for the "Bezpečně odebrat" fix button on a type flagged by the log analyzer — works regardless of what's currently selected. */
    public function removeTypeByName(
        string $name,
        TypesXmlEditor $typesEditor,
        ConfigurationRevisionEditor $revisionEditor,
    ): void {
        $this->performTypeRemoval($name, $typesEditor, $revisionEditor);
        if ($this->selectedType === $name) {
            $this->selectedType = null;
        }
    }

    private function performTypeRemoval(
        string $name,
        TypesXmlEditor $typesEditor,
        ConfigurationRevisionEditor $revisionEditor,
    ): void {
        $content = $typesEditor->remove($this->rawContent, $name);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            "Odebrána položka {$name}",
            auth()->user(),
        );

        $this->loadRevision($revision);

        Notification::make()
            ->success()
            ->title("Revize #{$revision->revision_number} uložena")
            ->body("Položka {$name} byla odebrána z types.xml.")
            ->send();
    }

    /** @return list<string> */
    private function commaSeparatedValues(string $value, string $field): array
    {
        $items = array_values(array_unique(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value),
        ))));
        if (collect($items)->contains(static fn (string $item): bool => preg_match('/^[A-Za-z0-9_.-]+$/', $item) !== 1)) {
            throw ValidationException::withMessages([
                $field => 'Použijte názvy oddělené čárkou; povolena jsou písmena, čísla, tečka, podtržítko a pomlčka.',
            ]);
        }

        return $items;
    }

    public function saveRaw(
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        $validated = $this->validate([
            'rawContent' => ['required', 'string'],
            'changeSummary' => ['nullable', 'string', 'max:500'],
        ]);

        $sourceRevision = $this->sourceRevision();
        $filename = $sourceRevision->configurationImport?->original_filename ?? basename($sourceRevision->storage_path);
        $compatibility->assertEditable($this->getRecord(), $validated['rawContent'], [$filename]);

        $revision = $revisionEditor->save(
            $this->getRecord(),
            $sourceRevision,
            $validated['rawContent'],
            $validated['changeSummary'] ?: 'Úprava raw konfigurace',
            auth()->user(),
        );

        $this->loadRevision($revision);

        Notification::make()
            ->success()
            ->title("Revize #{$revision->revision_number} uložena")
            ->body('Raw konfigurace prošla validací.')
            ->send();
    }

    public function saveServerConfig(ServerConfigEditor $editor, ConfigurationRevisionEditor $revisionEditor): void
    {
        $project = $this->getRecord();
        $revision = $project->revisions()->findOrFail($this->revisionId);
        $original = $editor->parse($this->rawContent);
        foreach (['password', 'passwordAdmin'] as $secret) {
            if (array_key_exists($secret, $this->serverConfig) && trim((string) $this->serverConfig[$secret]) === '') {
                $this->serverConfig[$secret] = $original[$secret] ?? '';
            }
        }
        $content = $editor->update($this->rawContent, $this->serverConfig);
        $saved = $revisionEditor->save($project, $revision, $content, 'Úprava serverDZ.cfg ve vizuálním editoru', auth()->user());
        $this->loadRevision($saved);
    }

    public function addWhitelistEntry(): void
    {
        $id = trim($this->newWhitelistId);
        if ($id === '' || ! preg_match('/^[A-Za-z0-9_-]{3,64}$/', $id) || $this->idListContains($this->whitelistEntries, $id)) {
            return;
        }
        $this->whitelistEntries[] = ['id' => $id, 'comment' => trim($this->newWhitelistComment)];
        $this->newWhitelistId = '';
        $this->newWhitelistComment = '';
    }

    public function removeWhitelistEntry(int $index): void
    {
        unset($this->whitelistEntries[$index]);
        $this->whitelistEntries = array_values($this->whitelistEntries);
    }

    public function saveWhitelist(ConfigurationRevisionEditor $revisionEditor): void
    {
        $content = $this->serializeIdCommentLines($this->whitelistEntries);
        $saved = $revisionEditor->save($this->getRecord(), $this->sourceRevision(), $content, 'Úprava whitelist.txt ve vizuálním editoru', auth()->user());
        $this->loadRevision($saved);
    }

    public function addBanEntry(): void
    {
        $id = trim($this->newBanId);
        if ($id === '' || ! preg_match('/^[A-Za-z0-9_-]{3,64}$/', $id) || $this->idListContains($this->banEntries, $id)) {
            return;
        }
        $this->banEntries[] = ['id' => $id, 'comment' => trim($this->newBanComment)];
        $this->newBanId = '';
        $this->newBanComment = '';
    }

    public function removeBanEntry(int $index): void
    {
        unset($this->banEntries[$index]);
        $this->banEntries = array_values($this->banEntries);
    }

    public function saveBan(ConfigurationRevisionEditor $revisionEditor): void
    {
        $content = $this->serializeIdCommentLines($this->banEntries);
        $saved = $revisionEditor->save($this->getRecord(), $this->sourceRevision(), $content, 'Úprava ban.txt ve vizuálním editoru', auth()->user());
        $this->loadRevision($saved);
    }

    public function addPriorityEntry(): void
    {
        $id = trim($this->newPriorityId);
        if ($id === '' || ! preg_match('/^[A-Za-z0-9 _;-]{3,128}$/', $id) || $this->idListContains($this->priorityEntries, $id)) {
            return;
        }
        $this->priorityEntries[] = ['id' => $id, 'comment' => trim($this->newPriorityComment)];
        $this->newPriorityId = '';
        $this->newPriorityComment = '';
    }

    public function removePriorityEntry(int $index): void
    {
        unset($this->priorityEntries[$index]);
        $this->priorityEntries = array_values($this->priorityEntries);
    }

    public function savePriority(ConfigurationRevisionEditor $revisionEditor): void
    {
        $content = $this->serializeIdCommentLines($this->priorityEntries);
        $saved = $revisionEditor->save($this->getRecord(), $this->sourceRevision(), $content, 'Úprava priority.txt ve vizuálním editoru', auth()->user());
        $this->loadRevision($saved);
    }

    /** @param list<array{id: string, comment: string}> $entries */
    private function idListContains(array $entries, string $id): bool
    {
        foreach ($entries as $entry) {
            if (($entry['id'] ?? '') === $id) {
                return true;
            }
        }

        return false;
    }

    /** Parses "ID" or "ID // comment" (any whitespace, e.g. a real tab, before the //) lines used by whitelist.txt/ban.txt/priority.txt. */
    private function parseIdCommentLines(string $content): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $content) ?: []), fn (string $line): bool => $line !== ''));

        return array_map(function (string $line): array {
            $pos = strpos($line, '//');
            if ($pos === false) {
                return ['id' => $line, 'comment' => ''];
            }

            return ['id' => trim(substr($line, 0, $pos)), 'comment' => trim(substr($line, $pos + 2))];
        }, $lines);
    }

    /** @param list<array{id: string, comment: string}> $entries */
    private function serializeIdCommentLines(array $entries): string
    {
        $lines = [];
        $seen = [];
        foreach ($entries as $entry) {
            $id = trim((string) ($entry['id'] ?? ''));
            if ($id === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $comment = trim((string) ($entry['comment'] ?? ''));
            $lines[] = $comment !== '' ? "{$id}\t//{$comment}" : $id;
        }

        return $lines === [] ? '' : implode("\n", $lines)."\n";
    }

    public function saveMessages(ConfigurationRevisionEditor $revisionEditor, PlatformCompatibility $compatibility): void
    {
        if ($this->visualKind !== 'messages') {
            return;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;
        if (! @$document->loadXML($this->rawContent, LIBXML_NONET | LIBXML_COMPACT)) {
            throw ValidationException::withMessages(['rawContent' => 'messages.xml není platné XML.']);
        }
        $root = $document->documentElement;
        if (! $root || $root->tagName !== 'messages') {
            throw ValidationException::withMessages(['rawContent' => 'Kořenový element musí být <messages>.']);
        }
        foreach (iterator_to_array($root->childNodes) as $node) {
            if ($node instanceof \DOMElement && $node->tagName === 'message') $root->removeChild($node);
        }
        foreach ($this->messagesEntries as $entry) {
            $message = $document->createElement('message');
            $root->appendChild($message);
            foreach (['deadline', 'shutdown', 'repeat', 'delay', 'onconnect'] as $key) {
                $child = null;
                foreach ($message->childNodes as $candidate) {
                    if ($candidate instanceof \DOMElement && $candidate->tagName === $key) {
                        $child = $candidate;
                        break;
                    }
                }
                $value = trim((string) ($entry[$key] ?? ''));
                if ($value === '') {
                    if ($child) $message->removeChild($child);
                    continue;
                }
                if (! $child) {
                    $child = $document->createElement($key);
                    $message->appendChild($child);
                }
                $child->nodeValue = $value;
            }
            $text = null;
            foreach ($message->childNodes as $candidate) {
                if ($candidate instanceof \DOMElement && $candidate->tagName === 'text') {
                    $text = $candidate;
                    break;
                }
            }
            if (! $text) {
                $text = $document->createElement('text');
                $message->appendChild($text);
            }
            $text->nodeValue = (string) ($entry['text'] ?? '');
        }
        $content = $document->saveXML() ?: $this->rawContent;
        $compatibility->assertEditable($this->getRecord(), $content, ['messages.xml']);
        $revision = $revisionEditor->save($this->getRecord(), $this->sourceRevision(), $content, $this->changeSummary ?: 'Úprava messages.xml', auth()->user());
        $this->loadRevision($revision);
        Notification::make()->success()->title("messages.xml uloženo v revizi #{$revision->revision_number}")->send();
    }

    public function addMessage(): void
    {
        $this->messagesEntries[] = ['deadline' => '', 'shutdown' => '', 'repeat' => '', 'delay' => '', 'onconnect' => '', 'text' => '', 'type' => 'broadcast'];
    }

    public function removeMessage(int $index): void
    {
        unset($this->messagesEntries[$index]);
        $this->messagesEntries = array_values($this->messagesEntries);
    }

    public function saveWeather(
        WeatherXmlEditor $weatherEditor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        if ($this->visualKind !== 'weather') {
            return;
        }

        $validated = $this->validate([
            'weatherForm' => ['required', 'array'],
            'weatherForm.*' => ['nullable'],
            'changeSummary' => ['nullable', 'string', 'max:500'],
        ]);
        foreach ($validated['weatherForm'] as $key => $value) {
            if (in_array($key, ['reset', 'enable'], true)) {
                continue;
            }
            if (! is_numeric($value)) {
                throw ValidationException::withMessages(["weatherForm.{$key}" => 'Hodnota musí být číslo.']);
            }
        }

        $content = $weatherEditor->update($this->rawContent, $validated['weatherForm']);
        $compatibility->assertEditable($this->getRecord(), $content, ['cfgweather.xml']);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            $validated['changeSummary'] ?: 'Úprava počasí',
            auth()->user(),
        );

        $this->loadRevision($revision);

        Notification::make()
            ->success()
            ->title("Počasí uloženo v revizi #{$revision->revision_number}")
            ->send();
    }

    public function saveJson(JsonConfigurationEditor $jsonEditor, ConfigurationRevisionEditor $revisionEditor, PlatformCompatibility $compatibility): void
    {
        $content = $jsonEditor->update($this->rawContent, $this->flattenJsonValues($this->jsonValues));
        $compatibility->assertEditable($this->getRecord(), $content, [$this->currentFilename]);
        $revision = $revisionEditor->save($this->getRecord(), $this->sourceRevision(), $content, $this->changeSummary ?: 'Úprava JSON konfigurace', auth()->user());
        $this->loadRevision($revision);
        Notification::make()->success()->title("Konfigurace uložena v revizi #{$revision->revision_number}")->send();
    }

    /** @return array<string, mixed> */
    private function flattenJsonValues(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value) && ! array_is_list($value)) {
                $flat += $this->flattenJsonValues($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    public function saveXml(XmlConfigurationEditor $xmlEditor, ConfigurationRevisionEditor $revisionEditor, PlatformCompatibility $compatibility): void
    {
        $content = $xmlEditor->update($this->rawContent, $this->xmlValues);
        $compatibility->assertEditable($this->getRecord(), $content, [$this->currentFilename]);
        $revision = $revisionEditor->save($this->getRecord(), $this->sourceRevision(), $content, $this->changeSummary ?: 'Úprava XML konfigurace', auth()->user());
        $this->loadRevision($revision);
        Notification::make()->success()->title("Konfigurace uložena v revizi #{$revision->revision_number}")->send();
    }

    public function saveEventSpawns(XmlConfigurationEditor $xmlEditor, ConfigurationRevisionEditor $revisionEditor, PlatformCompatibility $compatibility): void
    {
        foreach ($this->eventSpawns as $eventIndex => $event) {
            $name = trim((string) ($this->xmlValues[$event['name_path']] ?? ''));
            if ($name === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
                throw ValidationException::withMessages(['xmlValues' => 'Event #'.($eventIndex + 1).' má neplatný název.']);
            }
            foreach ($event['positions'] as $positionIndex => $position) {
                foreach (['x', 'z'] as $axis) {
                    $value = $this->xmlValues[$position[$axis.'_path']] ?? null;
                    if (! is_numeric($value) || (float) $value < 0 || (float) $value > 15360) {
                        throw ValidationException::withMessages(['xmlValues' => "{$name}, pozice #".($positionIndex + 1).": {$axis} musí být 0–15 360."]);
                    }
                }
                $angle = $this->xmlValues[$position['a_path']] ?? 0;
                if (! is_numeric($angle) || (float) $angle < 0 || (float) $angle >= 360) {
                    throw ValidationException::withMessages(['xmlValues' => "{$name}, pozice #".($positionIndex + 1).': natočení musí být 0 až méně než 360°.']);
                }
            }
        }
        $this->saveXml($xmlEditor, $revisionEditor, $compatibility);
    }

    /** @return list<array<string, mixed>> */
    public function filteredEventSpawns(): array
    {
        if ($this->eventSpawnSearch === '') {
            return array_slice($this->eventSpawns, 0, 200);
        }
        $search = mb_strtolower($this->eventSpawnSearch);

        return array_slice(array_values(array_filter(
            $this->eventSpawns,
            fn (array $event): bool => str_contains(mb_strtolower((string) ($this->xmlValues[$event['name_path']] ?? $event['name'])), $search),
        )), 0, 200);
    }

    public function toggleEventSpawn(string $namePath): void
    {
        $this->expandedEventSpawn = $this->expandedEventSpawn === $namePath ? null : $namePath;
    }

    public function saveEventGroups(
        XmlConfigurationEditor $xmlEditor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        foreach ($this->eventGroups as $groupIndex => $group) {
            $name = trim((string) ($this->xmlValues[$group['name_path']] ?? ''));
            if ($name === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
                throw ValidationException::withMessages([
                    'xmlValues' => 'Název skupiny #'.($groupIndex + 1).' smí obsahovat písmena, čísla, tečku, pomlčku a podtržítko.',
                ]);
            }

            foreach ($group['children'] as $childIndex => $child) {
                $prefix = 'Skupina '.$name.', objekt #'.($childIndex + 1);
                $type = trim((string) ($this->xmlValues[$child['type']['path']] ?? ''));
                if ($type === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $type)) {
                    throw ValidationException::withMessages(['xmlValues' => "{$prefix}: neplatný nebo prázdný název třídy."]);
                }

                foreach (['x', 'z', 'y'] as $axis) {
                    $value = $this->xmlValues[$child[$axis]['path']] ?? null;
                    if (! is_numeric($value) || (float) $value < -10000 || (float) $value > 10000) {
                        throw ValidationException::withMessages(['xmlValues' => "{$prefix}: {$axis} musí být relativní souřadnice od -10 000 do 10 000."]);
                    }
                }

                $angle = $this->xmlValues[$child['a']['path']] ?? null;
                if (! is_numeric($angle) || (float) $angle < 0 || (float) $angle > 360) {
                    throw ValidationException::withMessages(['xmlValues' => "{$prefix}: natočení musí být 0–360°."]);
                }

                $deloot = $this->xmlValues[$child['deloot']['path']] ?? null;
                if (! in_array((string) $deloot, ['0', '1'], true)) {
                    throw ValidationException::withMessages(['xmlValues' => "{$prefix}: DE loot musí být 0 nebo 1."]);
                }

                $lootMin = $this->xmlValues[$child['lootmin']['path']] ?? null;
                $lootMax = $this->xmlValues[$child['lootmax']['path']] ?? null;
                if (! ctype_digit((string) $lootMin) || ! ctype_digit((string) $lootMax)
                    || (int) $lootMin > 1000 || (int) $lootMax > 1000 || (int) $lootMin > (int) $lootMax) {
                    throw ValidationException::withMessages([
                        'xmlValues' => "{$prefix}: lootmin a lootmax musí být celá čísla 0–1000 a minimum nesmí převýšit maximum.",
                    ]);
                }
            }
        }

        $this->saveXml($xmlEditor, $revisionEditor, $compatibility);
    }

    public function addEventGroupChild(
        int $groupIndex,
        XmlConfigurationEditor $xmlEditor,
        EventGroupsXmlEditor $eventGroupsEditor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        $type = trim((string) ($this->newEventChildTypes[$groupIndex] ?? ''));
        if ($type === '' || ! preg_match('/^[A-Za-z0-9_.-]+$/', $type)) {
            throw ValidationException::withMessages([
                'newEventChildTypes.'.$groupIndex => 'Zadejte platný DayZ classname nového objektu.',
            ]);
        }
        $content = $xmlEditor->update($this->rawContent, $this->xmlValues);
        $content = $eventGroupsEditor->addChild($content, $groupIndex, $type);
        $compatibility->assertEditable($this->getRecord(), $content, [$this->currentFilename]);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            'Přidán objekt '.$type.' do skupiny '.($groupIndex + 1),
            auth()->user(),
        );
        $this->loadRevision($revision);
        Notification::make()->success()->title("Objekt přidán v revizi #{$revision->revision_number}")->send();
    }

    public function removeEventGroupChild(
        int $groupIndex,
        int $childIndex,
        XmlConfigurationEditor $xmlEditor,
        EventGroupsXmlEditor $eventGroupsEditor,
        ConfigurationRevisionEditor $revisionEditor,
        PlatformCompatibility $compatibility,
    ): void {
        $content = $xmlEditor->update($this->rawContent, $this->xmlValues);
        $content = $eventGroupsEditor->removeChild($content, $groupIndex, $childIndex);
        $compatibility->assertEditable($this->getRecord(), $content, [$this->currentFilename]);
        $revision = $revisionEditor->save(
            $this->getRecord(),
            $this->sourceRevision(),
            $content,
            'Odebrán objekt #'.($childIndex + 1).' ze skupiny '.($groupIndex + 1),
            auth()->user(),
        );
        $this->loadRevision($revision);
        Notification::make()->success()->title("Objekt odebrán v revizi #{$revision->revision_number}")->send();
    }

    private function loadLatestRevision(): void
    {
        $revision = $this->getRecord()->revisions()
            ->with('configurationImport')
            ->latest('revision_number')
            ->firstOrFail();

        $this->loadRevision($revision);
    }

    private function loadRevision(ConfigurationRevision $revision): void
    {
        $revision->loadMissing('configurationImport');
        $this->revisionId = $revision->id;
        $this->revisionNumber = $revision->revision_number;
        $this->rawContent = app(ConfigurationRevisionEditor::class)->content($revision);
        $this->changeSummary = '';

        $filename = $revision->configurationImport?->original_filename ?? basename($revision->storage_path);
        $this->currentFilename = $filename;
        $typesEditor = app(TypesXmlEditor::class);
        $weatherEditor = app(WeatherXmlEditor::class);
        $jsonEditor = app(JsonConfigurationEditor::class);
        $xmlEditor = app(XmlConfigurationEditor::class);
        $eventGroupsEditor = app(EventGroupsXmlEditor::class);
        $eventsEditor = app(EventsXmlEditor::class);
        $environmentEditor = app(EnvironmentXmlEditor::class);
        $this->visualKind = match (true) {
            strtolower(basename($filename)) === 'serverdz.cfg' => 'server',
            strtolower(basename($filename)) === 'whitelist.txt' => 'whitelist',
            strtolower(basename($filename)) === 'ban.txt' => 'ban',
            strtolower(basename($filename)) === 'priority.txt' => 'priority',
            strtolower(basename($filename)) === 'messages.xml' => 'messages',
            strtolower(basename($filename)) === 'cfgeventspawns.xml' => 'event-spawns',
            $eventsEditor->supports($filename, $this->rawContent) => 'events',
            $environmentEditor->supports($filename, $this->rawContent) => 'environment',
            $typesEditor->supports($filename, $this->rawContent) => 'types',
            $weatherEditor->supports($filename, $this->rawContent) => 'weather',
            $jsonEditor->supports($this->rawContent) => 'json',
            $eventGroupsEditor->supports($filename, $this->rawContent) => 'event-groups',
            $this->isGeneratedMapExport($filename) => 'map-file',
            $xmlEditor->supports($this->rawContent) => 'xml',
            default => null,
        };
        $this->visualSupported = $this->visualKind !== null;
        $this->typeEntries = $this->visualKind === 'types' ? $typesEditor->entries($this->rawContent) : [];
        $this->eventEntries = $this->visualKind === 'events' ? $eventsEditor->entries($this->rawContent) : [];
        $this->environmentEntries = $this->visualKind === 'environment' ? $environmentEditor->entries($this->rawContent) : [];
        $this->weatherForm = $this->visualKind === 'weather' ? $weatherEditor->values($this->rawContent) : [];
        $this->serverConfig = $this->visualKind === 'server' ? app(ServerConfigEditor::class)->parse($this->rawContent) : [];
        $this->whitelistEntries = $this->visualKind === 'whitelist' ? $this->parseIdCommentLines($this->rawContent) : [];
        $this->banEntries = $this->visualKind === 'ban' ? $this->parseIdCommentLines($this->rawContent) : [];
        $this->priorityEntries = $this->visualKind === 'priority' ? $this->parseIdCommentLines($this->rawContent) : [];
        $this->jsonFields = $this->visualKind === 'json' ? $jsonEditor->fields($this->rawContent) : [];
        $this->jsonValues = [];
        foreach ($this->jsonFields as $field) {
            data_set($this->jsonValues, $field['path'], $field['value']);
        }
        $this->xmlFields = in_array($this->visualKind, ['xml', 'event-groups', 'event-spawns'], true) ? $xmlEditor->fields($this->rawContent) : [];
        $this->xmlValues = collect($this->xmlFields)->mapWithKeys(fn (array $field): array => [$field['path'] => $field['value']])->all();
        $this->eventGroups = $this->visualKind === 'event-groups'
            ? $eventGroupsEditor->groups($this->rawContent)
            : [];
        $this->eventSpawns = $this->visualKind === 'event-spawns' ? $this->parseEventSpawns($this->rawContent) : [];
        $this->newEventChildTypes = [];
        $this->messagesEntries = $this->visualKind === 'messages' ? $this->parseMessages($this->rawContent) : [];
        $this->mode = $this->visualSupported ? 'visual' : 'raw';

        $detection = app(PlatformDetector::class)->detect($this->rawContent, [$filename]);
        $this->detectedPlatform = $detection->platform;
        $this->platformReasons = $detection->platform === 'steam' ? $detection->reasons : [];
        $this->platformWarnings = $detection->warnings;
        $this->dependencyWarnings = $this->detectDependencyWarnings($filename);
    }

    private function isGeneratedMapExport(string $filename): bool
    {
        $filename = strtolower(basename($filename));

        return $filename === 'mapclusterproto.xml'
            || $filename === 'mapgroupproto.xml'
            || $filename === 'mapgroupdirt.xml'
            || $filename === 'mapgrouppos.xml'
            || $filename === 'cfgplayerspawnpoints.xml'
            || str_ends_with($filename, '_territories.xml')
            || preg_match('/^mapgroupcluster(?:\d+)?\.xml$/', $filename) === 1;
    }

    /** @return list<array{name:string,name_path:string,positions:list<array{x_path:string,z_path:string,a_path:string}>}> */
    private function parseEventSpawns(string $content): array
    {
        $document = new \DOMDocument();
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT) || $document->documentElement?->tagName !== 'eventposdef') {
            return [];
        }
        $result = [];
        $eventIndex = 0;
        $visiblePositions = 0;
        foreach ($document->documentElement->childNodes as $event) {
            if (! $event instanceof \DOMElement || $event->tagName !== 'event') {
                continue;
            }
            $eventIndex++;
            $positions = [];
            $positionIndex = 0;
            foreach ($event->childNodes as $position) {
                if (! $position instanceof \DOMElement || $position->tagName !== 'pos') {
                    continue;
                }
                // cfgeventspawns.xml commonly contains thousands of positions.
                // Keep the Livewire snapshot/browser responsive; saveXml updates
                // only the displayed paths and leaves the rest of rawContent intact.
                if ($visiblePositions >= 200) {
                    continue;
                }
                $positionIndex++;
                $visiblePositions++;
                $base = "/eventposdef[1]/event[{$eventIndex}]/pos[{$positionIndex}]";
                $positions[] = ['x_path' => $base.'@x', 'z_path' => $base.'@z', 'a_path' => $base.'@a'];
            }
            $result[] = [
                'name' => $event->getAttribute('name'),
                'name_path' => "/eventposdef[1]/event[{$eventIndex}]@name",
                'positions' => $positions,
            ];
        }

        return $result;
    }

    /** @return list<string> */
    private function detectDependencyWarnings(string $filename): array
    {
        $warnings = [];
        $lower = strtolower(basename($filename));
        $revisions = $this->getRecord()->revisions()->with('configurationImport')->latest('revision_number')->get();
        $byName = fn (string $name) => $revisions->first(fn ($revision) => strtolower($revision->configurationImport?->original_filename ?? '') === strtolower($name));
        if ($lower === 'cfggameplay.json' || (str_ends_with($lower, '.json') && (str_contains($lower, 'spawner') || str_contains($lower, 'gear')))) {
            $server = $byName('serverDZ.cfg');
            $enabled = false;
            if ($server) {
                try {
                    $config = app(ServerConfigEditor::class)->parse(app(ConfigurationRevisionEditor::class)->content($server));
                    $enabled = in_array(strtolower((string) ($config['enableCfgGameplayFile'] ?? '0')), ['1', 'true'], true);
                } catch (Throwable) {
                    $enabled = false;
                }
            }
            if (! $enabled) $warnings[] = 'cfggameplay.json a navázané JSON soubory vyžadují enableCfgGameplayFile = 1 v serverDZ.cfg.';
        }
        if ($lower === 'cfggameplay.json') {
            $decoded = json_decode($this->rawContent, true);
            foreach (['objectSpawnersArr', 'spawnGearPresetFiles'] as $key) {
                $files = data_get($decoded, 'PlayerData.'.$key, data_get($decoded, 'WorldData.'.$key, data_get($decoded, $key, [])));
                foreach (is_array($files) ? $files : [] as $required) {
                    if (is_string($required) && ! $byName(basename($required))) {
                        $warnings[] = "{$key} odkazuje na chybějící soubor {$required}.";
                    }
                }
            }
        }
        return array_values(array_unique($warnings));
    }

    /** @return list<array<string, string>> */
    private function parseMessages(string $content): array
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        if (! @$document->loadXML($content, LIBXML_NONET | LIBXML_COMPACT)) return [];
        $entries = [];
        foreach ((new \DOMXPath($document))->query('/messages/message') ?: [] as $message) {
            $entry = array_fill_keys(['deadline', 'shutdown', 'repeat', 'delay', 'onconnect', 'text'], '');
            foreach ($message->childNodes as $child) {
                if ($child instanceof \DOMElement && array_key_exists($child->tagName, $entry)) {
                    $entry[$child->tagName] = trim($child->textContent);
                }
            }
            $entry['type'] = $this->messageType($entry);
            $entries[] = $entry;
        }
        return $entries;
    }

    private function messageType(array $entry): string
    {
        return match (true) {
            (string) ($entry['onconnect'] ?? '') === '1' => 'onconnect',
            (string) ($entry['shutdown'] ?? '') === '1' => 'shutdown',
            default => 'broadcast',
        };
    }

    /** Keeps onconnect/shutdown flags (the real XML fields) in sync with the friendlier "Typ zprávy" picker. */
    public function updated($name, $value): void
    {
        if (! preg_match('/^messagesEntries\.(\d+)\.type$/', (string) $name, $matches)) {
            return;
        }
        $index = (int) $matches[1];
        if (! isset($this->messagesEntries[$index])) {
            return;
        }
        $type = $this->messagesEntries[$index]['type'] ?? 'broadcast';
        $this->messagesEntries[$index]['onconnect'] = $type === 'onconnect' ? '1' : '0';
        $this->messagesEntries[$index]['shutdown'] = $type === 'shutdown' ? '1' : '0';
        if ($type === 'onconnect') {
            $this->messagesEntries[$index]['repeat'] = '';
            $this->messagesEntries[$index]['delay'] = '';
            $this->messagesEntries[$index]['deadline'] = '';
        }
        if ($type === 'broadcast') {
            $this->messagesEntries[$index]['deadline'] = '';
        }
    }

    private function sourceRevision(): ConfigurationRevision
    {
        $revision = $this->getRecord()->revisions()
            ->with('configurationImport')
            ->findOrFail($this->revisionId);

        if (! in_array($this->getRecord()->platform, ['playstation', 'xbox', 'steam', 'unknown'], true)) {
            throw ValidationException::withMessages(['rawContent' => 'Neznámá platforma projektu.']);
        }

        return $revision;
    }
}
