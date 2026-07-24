<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\ConfigurationRevision;
use App\Services\Import\ConfigurationImporter;
use App\Services\PlatformDetection\PlatformCompatibility;
use App\Services\PlatformDetection\PlatformDetector;
use App\Services\Revision\ConfigurationRevisionEditor;
use App\Services\Revision\TypesXmlEditor;
use App\Services\Revision\WeatherXmlEditor;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
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

    /** @var array<string, int> */
    public array $typeForm = [];

    /** @var list<array<string, mixed>> */
    public array $typeEntries = [];

    /** @var list<array<string, mixed>> */
    public array $catalogEntries = [];

    public bool $visualSupported = false;

    public ?string $visualKind = null;

    /** @var array<string, mixed> */
    public array $weatherForm = [];

    public bool $showAddForm = false;

    public bool $showClassPicker = false;

    public string $classPickerCategory = 'weapons';

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

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->loadCatalog();
        $requestedRevision = request()->integer('revision');
        $requested = $requestedRevision
            ? $this->getRecord()->revisions()->whereKey($requestedRevision)->first()
            : null;
        if ($requested) {
            $this->loadRevision($requested);
        } else {
            $this->loadLatestRevision();
        }
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
        return [ProjectResource::getUrl() => 'Projekty'];
    }

    public function configurationDescription(): string
    {
        return $this->descriptionForFilename($this->currentFilename);
    }

    public function descriptionForFilename(string $filename): string
    {
        return match (strtolower($filename)) {
            'globals.xml' => 'Globální limity zvířat, infikovaných, loot economy a cleanup serveru.',
            'events.xml' => 'Počty, minima, maxima a životnost dynamických eventů jako zombie, loot nebo heli crash.',
            'cfgeventspawns.xml' => 'Souřadnice a orientace pevných eventů na mapě.',
            'cfgspawnabletypes.xml' => 'Obsah kontejnerů, cargo a attachmenty, které se mohou spawnout uvnitř předmětu.',
            'cfglimitsdefinition.xml' => 'Kategorie, usage flagy a definice limitů pro ekonomiku.',
            'mapgrouppos.xml' => 'Pozice skupin budov a loot zón na mapě.',
            'economycore.xml' => 'Základní chování dynamické ekonomiky a respawnu.',
            'messages.xml' => 'Automatické serverové zprávy, intervaly a jejich životnost.',
            'cfggameplay.json' => 'Gameplay nastavení: stamina, damage, respawn, UI, svět a pohyb hráče.',
            default => 'Pokročilá konfigurace serveru. Před uložením se ověří syntaxe XML nebo JSON.',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadConfiguration')
                ->label('Nahrát novou konfiguraci')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Nová konfigurace pro '.$this->getRecord()->name)
                ->modalDescription('XML, JSON nebo ZIP se zvaliduje a uloží jako nová revize tohoto projektu.')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Konfigurační soubor')
                        ->acceptedFileTypes([
                            'application/xml',
                            'text/xml',
                            'text/plain',
                            'application/json',
                            'application/zip',
                            'application/x-zip-compressed',
                        ])
                        ->maxSize((int) config('dayz.max_upload_size'))
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data, Actions\Action $action): void {
                    try {
                        $file = $data['file'];

                        if (! $file instanceof TemporaryUploadedFile) {
                            throw new \RuntimeException('Nahraný soubor není dostupný.');
                        }

                        $import = app(ConfigurationImporter::class)->import(
                            $this->getRecord(),
                            $file,
                            auth()->user(),
                        );
                        $revision = $import->revisions()->latest('revision_number')->firstOrFail();
                        $this->revisionId = $revision->id;
                        $this->loadRevision();

                        Notification::make()
                            ->success()
                            ->title('Konfigurace byla nahrána')
                            ->body("{$import->original_filename} · validace: {$import->validation_status}")
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Nahrání se nezdařilo')
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
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
            ->unique(fn (ConfigurationRevision $revision): string => (string) (
                $revision->configuration_import_id ?: $revision->storage_path
            ))
            ->mapWithKeys(fn (ConfigurationRevision $revision): array => [
                $revision->id => ($revision->configurationImport?->original_filename ?? basename($revision->storage_path))
                    ." · revize #{$revision->revision_number}",
            ])
            ->all();
    }

    public function switchRevision(): void
    {
        $revision = $this->getRecord()->revisions()
            ->with('configurationImport')
            ->findOrFail($this->revisionId);
        $this->selectedType = null;
        $this->showAddForm = false;
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
        ])->map(static fn ($value): int => (int) $value)->all();
        $this->showAddForm = false;
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

    public function pickerEntries(): array
    {
        return array_values(array_filter(
            $this->catalogEntries,
            fn (array $entry): bool => ($entry['category'] ?: 'other') === $this->classPickerCategory,
        ));
    }

    public function pickerCategories(): array
    {
        return collect($this->catalogEntries)
            ->pluck('category')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function loadCatalog(): void
    {
        $path = base_path('database/seeders/fixtures/dayz-types-chernarus.xml');
        if (! is_file($path)) {
            return;
        }

        $content = file_get_contents($path);
        if ($content !== false) {
            $this->catalogEntries = app(TypesXmlEditor::class)->entries($content);
        }
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
            'typeForm.nominal' => ['required', 'integer', 'min:0', 'max:100000'],
            'typeForm.lifetime' => ['required', 'integer', 'min:0', 'max:3888000'],
            'typeForm.restock' => ['required', 'integer', 'min:0', 'max:3888000'],
            'typeForm.min' => ['required', 'integer', 'min:0', 'max:100000'],
            'typeForm.quantmin' => ['required', 'integer', 'min:-1', 'max:100'],
            'typeForm.quantmax' => ['required', 'integer', 'min:-1', 'max:100'],
            'typeForm.cost' => ['required', 'integer', 'min:0', 'max:1000'],
            'changeSummary' => ['nullable', 'string', 'max:500'],
        ]);

        $content = $typesEditor->update($this->rawContent, $this->selectedType, $validated['typeForm']);
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
        $this->visualKind = match (true) {
            $typesEditor->supports($filename, $this->rawContent) => 'types',
            $weatherEditor->supports($filename, $this->rawContent) => 'weather',
            default => null,
        };
        $this->visualSupported = $this->visualKind !== null;
        $this->typeEntries = $this->visualKind === 'types' ? $typesEditor->entries($this->rawContent) : [];
        $this->weatherForm = $this->visualKind === 'weather' ? $weatherEditor->values($this->rawContent) : [];
        $this->mode = $this->visualSupported ? 'visual' : 'raw';

        $detection = app(PlatformDetector::class)->detect($this->rawContent, [$filename]);
        $this->detectedPlatform = $detection->platform;
        $this->platformReasons = $detection->platform === 'steam' ? $detection->reasons : [];
        $this->platformWarnings = $detection->warnings;
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
