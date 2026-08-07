<?php

namespace Tests\Feature;

use App\Filament\Pages\MapEditor;
use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MapEditorSpawnWarningsTest extends TestCase
{
    use RefreshDatabase;

    private function seedSpawnPoints(Project $project, User $user, string $content): void
    {
        $path = "{$project->id}/test/cfgplayerspawnpoints.xml";
        Storage::disk('dayz')->put($path, $content);
        $sha256 = hash('sha256', $content);
        $import = ConfigurationImport::query()->create([
            'project_id' => $project->id,
            'sha256' => $sha256,
            'original_filename' => 'cfgplayerspawnpoints.xml',
            'storage_path' => $path,
            'detected_platform' => $project->platform,
            'detection_confidence' => 65,
            'validation_status' => 'valid',
            'imported_at' => now(),
        ]);
        ConfigurationRevision::query()->create([
            'project_id' => $project->id,
            'revision_number' => 1,
            'configuration_import_id' => $import->id,
            'storage_path' => $path,
            'sha256' => $sha256,
            'change_summary' => 'test',
            'created_by' => $user->id,
        ]);
    }

    public function test_map_editor_warns_when_a_spawn_mode_has_no_points(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedSpawnPoints($project, $user, <<<'XML'
<playerspawnpoints>
    <fresh><generator_posbubbles><group name="DevSpawn"><pos x="1" z="2"/></group></generator_posbubbles></fresh>
    <hop><generator_posbubbles><group name="Empty"/></generator_posbubbles></hop>
    <travel><generator_posbubbles><group name="Empty"/></generator_posbubbles></travel>
</playerspawnpoints>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnPointWarnings')
            ->assertSet('spawnPointWarnings', [
                ['mode' => 'hop', 'label' => 'HOP · změna serveru'],
                ['mode' => 'travel', 'label' => 'TRAVEL · cestovní přesun'],
            ]);
    }

    public function test_map_editor_has_no_warning_when_every_mode_has_a_point(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedSpawnPoints($project, $user, <<<'XML'
<playerspawnpoints>
    <fresh><generator_posbubbles><group name="A"><pos x="1" z="2"/></group></generator_posbubbles></fresh>
    <hop><generator_posbubbles><group name="B"><pos x="3" z="4"/></group></generator_posbubbles></hop>
    <travel><generator_posbubbles><group name="C"><pos x="5" z="6"/></group></generator_posbubbles></travel>
</playerspawnpoints>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnPointWarnings')
            ->assertSet('spawnPointWarnings', []);
    }

    private function seedEventSpawns(Project $project, User $user, string $content): void
    {
        $this->seedFile($project, $user, 'cfgeventspawns.xml', $content);
    }

    private function seedEnvironment(Project $project, User $user, string $content): void
    {
        $this->seedFile($project, $user, 'cfgenvironment.xml', $content);
    }

    private function seedEvents(Project $project, User $user, string $content): void
    {
        $this->seedFile($project, $user, 'events.xml', $content);
    }

    private function seedFile(Project $project, User $user, string $filename, string $content): void
    {
        $path = "{$project->id}/test/{$filename}";
        Storage::disk('dayz')->put($path, $content);
        $sha256 = hash('sha256', $content);
        $import = ConfigurationImport::query()->create([
            'project_id' => $project->id,
            'sha256' => $sha256,
            'original_filename' => $filename,
            'storage_path' => $path,
            'detected_platform' => $project->platform,
            'detection_confidence' => 65,
            'validation_status' => 'valid',
            'imported_at' => now(),
        ]);
        ConfigurationRevision::query()->create([
            'project_id' => $project->id,
            'revision_number' => (int) $project->revisions()->max('revision_number') + 1,
            'configuration_import_id' => $import->id,
            'storage_path' => $path,
            'sha256' => $sha256,
            'change_summary' => 'test',
            'created_by' => $user->id,
        ]);
    }

    public function test_map_editor_warns_about_event_spawns_referencing_undefined_event(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="StaticHeliCrash"><nominal>1</nominal></event></events>');
        $this->seedEventSpawns($project, $user, '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event><event name="VehicleTransitBus"><pos x="3" z="4" a="0"/></event></eventposdef>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadEventSpawnWarnings')
            ->assertSet('eventSpawnWarnings', ['VehicleTransitBus']);
    }

    public function test_map_editor_has_no_event_warning_when_all_events_are_defined(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="StaticHeliCrash"><nominal>1</nominal></event></events>');
        $this->seedEventSpawns($project, $user, '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event></eventposdef>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadEventSpawnWarnings')
            ->assertSet('eventSpawnWarnings', []);
    }

    public function test_removing_event_spawn_positions_clears_the_warning(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="StaticHeliCrash"><nominal>1</nominal></event></events>');
        $this->seedEventSpawns($project, $user, '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event><event name="GhostEvent"><pos x="3" z="4" a="0"/></event></eventposdef>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadEventSpawnWarnings')
            ->assertSet('eventSpawnWarnings', ['GhostEvent'])
            ->call('removeEventSpawnPositions', 'GhostEvent')
            ->assertSet('eventSpawnWarnings', []);

        $latest = $project->revisions()->orderByDesc('revision_number')->first();
        $this->assertStringNotContainsString('GhostEvent', Storage::disk('dayz')->get($latest->storage_path));
    }

    public function test_removing_event_spawn_positions_deletes_a_bare_event_with_no_positions(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="StaticHeliCrash"><nominal>1</nominal></event></events>');
        // VehicleTransitBus has no <pos> children at all — just an orphaned reference.
        $this->seedEventSpawns($project, $user, '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event><event name="VehicleTransitBus"/></eventposdef>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadEventSpawnWarnings')
            ->assertSet('eventSpawnWarnings', ['VehicleTransitBus'])
            ->call('removeEventSpawnPositions', 'VehicleTransitBus')
            ->assertSet('eventSpawnWarnings', []);

        $latest = $project->revisions()->orderByDesc('revision_number')->first();
        $this->assertStringNotContainsString('VehicleTransitBus', Storage::disk('dayz')->get($latest->storage_path));
    }

    public function test_open_event_query_parameter_opens_the_add_event_modal(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="StaticHeliCrash"><nominal>1</nominal></event></events>');
        $this->seedEventSpawns($project, $user, '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event><event name="VehicleTransitBus"><pos x="3" z="4" a="0"/></event></eventposdef>');

        $this->actingAs($user)
            ->get("/admin/map-editor?project={$project->id}&open_event=VehicleTransitBus")
            ->assertOk()
            ->assertSee('Přidat event „VehicleTransitBus“ do events.xml');
    }

    public function test_adding_missing_event_clears_the_warning_and_creates_a_valid_event(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="StaticHeliCrash"><nominal>1</nominal></event></events>');
        $this->seedEventSpawns($project, $user, '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event><event name="VehicleTransitBus"><pos x="3" z="4" a="0"/></event></eventposdef>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadEventSpawnWarnings')
            ->assertSet('eventSpawnWarnings', ['VehicleTransitBus'])
            ->call('openAddEventModal', 'VehicleTransitBus')
            ->assertSet('showAddEventModal', true)
            ->call('submitAddEvent')
            ->assertSet('showAddEventModal', false)
            ->assertSet('eventSpawnWarnings', []);

        $latest = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename ?? '')) === 'events.xml');
        $this->assertStringContainsString('name="VehicleTransitBus"', Storage::disk('dayz')->get($latest->storage_path));
    }

    private const ENVIRONMENT_XML = <<<'XML'
<env><territories>
    <file path="env/red_deer_territories.xml" />
    <file path="env/hen_territories.xml" />
    <file path="env/zombie_territories.xml" />
    <territory type="Herd" name="Deer" behavior="DZDeerGroupBeh"><file usable="red_deer_territories" /></territory>
    <territory type="Ambient" name="AmbientHen" behavior="DZAmbientLifeGroupBeh"><file usable="hen_territories" /></territory>
    <territory type="Herd" name="ZombieTest" behavior="DZdomesticGroupBeh"><file usable="zombie_territories" /></territory>
</territories></env>
XML;

    public function test_animal_population_warning_flags_a_herd_territory_missing_its_animal_prefixed_event(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="AmbientHen"><nominal>3</nominal></event></events>');
        $this->seedEnvironment($project, $user, self::ENVIRONMENT_XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadAnimalPopulationWarnings')
            ->assertSet('animalPopulationWarnings', [
                ['territory' => 'Deer', 'expected_event' => 'AnimalDeer'],
            ]);
    }

    public function test_animal_population_warning_is_clear_when_events_are_defined_and_infected_are_skipped(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="AnimalDeer"><nominal>5</nominal></event><event name="AmbientHen"><nominal>3</nominal></event></events>');
        $this->seedEnvironment($project, $user, self::ENVIRONMENT_XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadAnimalPopulationWarnings')
            ->assertSet('animalPopulationWarnings', []);
    }

    public function test_adding_an_animal_event_clears_the_population_warning(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEvents($project, $user, '<events><event name="AmbientHen"><nominal>3</nominal></event></events>');
        $this->seedEnvironment($project, $user, self::ENVIRONMENT_XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadAnimalPopulationWarnings')
            ->assertSet('animalPopulationWarnings', [['territory' => 'Deer', 'expected_event' => 'AnimalDeer']])
            ->call('openAddAnimalEventModal', 'Deer', 'AnimalDeer')
            ->assertSet('showAddEventModal', true)
            ->assertSet('addEventForm.limit', 'child')
            ->call('submitAddEvent')
            ->assertSet('showAddEventModal', false)
            ->assertSet('animalPopulationWarnings', []);
    }

    public function test_mapgrouppos_markers_are_colored_by_loot_category(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedFile($project, $user, 'mapgroupproto.xml', '<prototype><group name="Land_Mil_Barracks"><point pos="1 0 2"><category name="weapons"/></point></group><group name="Land_Village_House"><point pos="1 0 2"><category name="food"/></point></group></prototype>');
        $this->seedFile($project, $user, 'mapgrouppos.xml', '<map><group name="Land_Mil_Barracks" pos="5000 0 6000" rpy="0 0 0"/><group name="Land_Village_House" pos="7000 0 8000" rpy="0 0 0"/><group name="Land_Unknown_Shed" pos="9000 0 1000" rpy="0 0 0"/></map>');
        $this->seedFile($project, $user, 'types.xml', '<types><type name="AKM"><nominal>5</nominal><category name="weapons"/></type><type name="TunaCan"><nominal>5</nominal><category name="food"/></type></types>');

        $component = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->set('showDenseLayers', true)
            ->call('loadMarkers');

        $markers = collect($component->get('markers'));
        $barracks = $markers->firstWhere('label', 'Land_Mil_Barracks');
        $house = $markers->firstWhere('label', 'Land_Village_House');
        $shed = $markers->firstWhere('label', 'Land_Unknown_Shed');

        $this->assertSame(['weapons'], $barracks['categories']);
        $this->assertSame('#e96a5f', $barracks['color']);
        $this->assertSame(['food'], $house['categories']);
        $this->assertSame('#8fd15c', $house['color']);
        $this->assertSame([], $shed['categories']);
        $this->assertSame('#6b7a6d', $shed['color']);

        $legend = collect($component->get('lootCategoryLegend'))->keyBy('category');
        $this->assertSame(1, $legend['weapons']['item_count']);
        $this->assertSame(1, $legend['food']['item_count']);
    }

    private function seedTypes(Project $project, User $user, string $content): void
    {
        $this->seedFile($project, $user, 'types.xml', $content);
    }

    private const ENVIRONMENT_XML_WITH_AGENT = <<<'XML'
<env><territories>
    <file path="env/red_deer_territories.xml" />
    <territory type="Herd" name="Deer" behavior="DZDeerGroupBeh">
        <file usable="red_deer_territories" />
        <agent type="Male" chance="1"><spawn configName="Animal_CervusElaphus" chance="1" /></agent>
    </territory>
</territories></env>
XML;

    public function test_animal_type_warning_flags_a_classname_missing_from_types_xml(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, self::ENVIRONMENT_XML_WITH_AGENT);
        $this->seedTypes($project, $user, '<types><type name="AKM"><nominal>5</nominal></type></types>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadAnimalTypeWarnings')
            ->assertSet('animalTypeWarnings', [
                ['territory' => 'Deer', 'classname' => 'Animal_CervusElaphus'],
            ]);
    }

    public function test_animal_type_warning_is_clear_when_the_classname_is_already_defined(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, self::ENVIRONMENT_XML_WITH_AGENT);
        $this->seedTypes($project, $user, '<types><type name="Animal_CervusElaphus"><nominal>0</nominal></type></types>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadAnimalTypeWarnings')
            ->assertSet('animalTypeWarnings', []);
    }

    /**
     * The realistic case: a Herd species (like the real vanilla Deer) has no <agent> at all in
     * cfgenvironment.xml — its classnames live in the matching events.xml event's <children>
     * instead. This is the scenario an earlier version of loadAnimalTypeWarnings() silently
     * missed entirely, since it only ever looked at cfgenvironment.xml's own agents.
     */
    public function test_animal_type_warning_checks_herd_classnames_from_the_matching_events_xml_children(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        // Deer has NO <agent> child here, matching the real vanilla cfgenvironment.xml shape.
        $this->seedEnvironment($project, $user, '<env><territories><file path="env/red_deer_territories.xml" /><territory type="Herd" name="Deer" behavior="DZDeerGroupBeh"><file usable="red_deer_territories" /></territory></territories></env>');
        $this->seedEvents($project, $user, '<events><event name="AnimalDeer"><nominal>9</nominal><min>2</min><max>4</max><limit>child</limit><active>1</active><children><child max="1" min="1" type="Animal_CervusElaphus"/><child max="3" min="1" type="Animal_CervusElaphusF"/></children></event></events>');
        $this->seedTypes($project, $user, '<types><type name="Animal_CervusElaphus"><nominal>0</nominal></type></types>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadEventCatalog')
            ->call('loadAnimalTypeWarnings')
            ->assertSet('animalTypeWarnings', [
                ['territory' => 'Deer', 'classname' => 'Animal_CervusElaphusF'],
            ]);
    }

    public function test_adding_an_animal_type_entry_clears_the_warning_and_writes_expected_defaults(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, self::ENVIRONMENT_XML_WITH_AGENT);
        $this->seedTypes($project, $user, '<types><type name="AKM"><nominal>5</nominal></type></types>');

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadAnimalTypeWarnings')
            ->assertSet('animalTypeWarnings', [['territory' => 'Deer', 'classname' => 'Animal_CervusElaphus']])
            ->call('addAnimalTypeEntry', 'Animal_CervusElaphus')
            ->assertSet('animalTypeWarnings', []);

        $latest = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename ?? '')) === 'types.xml');
        $content = Storage::disk('dayz')->get($latest->storage_path);
        $this->assertStringContainsString('name="Animal_CervusElaphus"', $content);
        $this->assertMatchesRegularExpression('/Animal_CervusElaphus.*?<nominal>0<\/nominal>/s', $content);
    }

    public function test_zero_smax_dmax_is_not_flagged_for_a_herd_territory_since_events_xml_drives_its_population(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, <<<'XML'
<env><territories>
    <file path="env/wild_boar_territories.xml" />
    <territory type="Herd" name="WildBoar" behavior="DZWildBoarGroupBeh"><file usable="wild_boar_territories" /></territory>
</territories></env>
XML);
        $this->seedFile($project, $user, 'wild_boar_territories.xml', <<<'XML'
<territory-type><territory color="1"><zone name="Graze" smin="0" smax="0" dmin="0" dmax="0" x="9058.75" z="13568.8" r="60"/></territory></territory-type>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnValidationWarnings')
            ->assertSet('spawnValidationWarnings', fn (array $warnings): bool => ! collect($warnings)->contains(fn (array $warning) => ($warning['zero_population'] ?? false) === true));
    }

    public function test_zero_dmax_is_flagged_as_a_warning_not_a_critical_error_for_an_ambient_territory(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, <<<'XML'
<env><territories>
    <file path="env/hen_territories.xml" />
    <territory type="Ambient" name="Hen"><file usable="hen_territories" /></territory>
</territories></env>
XML);
        $this->seedFile($project, $user, 'hen_territories.xml', <<<'XML'
<territory-type><territory color="1"><zone name="Graze" smin="0" smax="0" dmin="0" dmax="0" x="1" z="2" r="60"/></territory></territory-type>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnValidationWarnings')
            ->assertSet('spawnValidationWarnings', fn (array $warnings): bool => collect($warnings)->contains(
                fn (array $warning) => $warning['severity'] === 'warning' && empty($warning['zero_population']) && str_contains($warning['detail'], 'dynamicky spawnovaných')
            ));
    }

    public function test_zero_smax_dmax_is_not_flagged_at_all_when_no_cfgenvironment_is_uploaded_and_the_file_matches_a_known_herd_species(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        // No cfgenvironment.xml uploaded at all — EnvironmentTargetCatalog's own fallback
        // list must still classify wild_boar_territories.xml as Herd.
        $this->seedFile($project, $user, 'wild_boar_territories.xml', <<<'XML'
<territory-type><territory color="1"><zone name="Graze" smin="0" smax="0" dmin="0" dmax="0" x="1" z="2" r="60"/></territory></territory-type>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnValidationWarnings')
            ->assertSet('spawnValidationWarnings', fn (array $warnings): bool => ! collect($warnings)->contains(fn (array $warning) => ($warning['zero_population'] ?? false) === true));
    }

    public function test_an_unregistered_territory_file_produces_one_warning_with_a_zone_count_not_one_per_zone(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        // A real, registered species (so $registeredTerritoryFiles isn't empty) — wild_boar
        // deliberately isn't registered here.
        $this->seedEnvironment($project, $user, <<<'XML'
<env><territories>
    <file path="env/red_deer_territories.xml" />
    <territory type="Herd" name="Deer"><file usable="red_deer_territories" /></territory>
</territories></env>
XML);
        $this->seedFile($project, $user, 'wild_boar_territories.xml', <<<'XML'
<territory-type><territory color="1">
    <zone name="Graze" smin="0" smax="5" dmin="0" dmax="5" x="1" z="2" r="60"/>
    <zone name="Graze" smin="0" smax="5" dmin="0" dmax="5" x="3" z="4" r="60"/>
    <zone name="Graze" smin="0" smax="5" dmin="0" dmax="5" x="5" z="6" r="60"/>
</territory></territory-type>
XML);

        $unregistered = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnValidationWarnings')
            ->get('spawnValidationWarnings');

        $matches = collect($unregistered)->filter(fn (array $warning) => ($warning['unregistered'] ?? false) === true);
        $this->assertCount(1, $matches, 'expected exactly one warning for the whole file, not one per zone');
        $this->assertSame(3, $matches->first()['zone_count']);
    }

    public function test_ignoring_a_territory_file_persists_on_the_project_and_suppresses_the_warning(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, <<<'XML'
<env><territories>
    <file path="env/red_deer_territories.xml" />
    <territory type="Herd" name="Deer"><file usable="red_deer_territories" /></territory>
</territories></env>
XML);
        $this->seedFile($project, $user, 'wild_boar_territories.xml', <<<'XML'
<territory-type><territory color="1"><zone name="Graze" smin="0" smax="5" dmin="0" dmax="5" x="1" z="2" r="60"/></territory></territory-type>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnValidationWarnings')
            ->assertSet('spawnValidationWarnings', fn (array $warnings): bool => collect($warnings)->contains(fn (array $warning) => ($warning['unregistered'] ?? false) === true))
            ->call('ignoreUnregisteredTerritoryFile', 'wild_boar_territories.xml')
            ->assertSet('spawnValidationWarnings', fn (array $warnings): bool => ! collect($warnings)->contains(fn (array $warning) => ($warning['unregistered'] ?? false) === true));

        $this->assertSame(['wild_boar_territories.xml'], $project->fresh()->ignored_territory_files);
    }

    public function test_deleting_a_territory_file_removes_its_import_revisions_and_storage(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedEnvironment($project, $user, <<<'XML'
<env><territories>
    <file path="env/red_deer_territories.xml" />
    <territory type="Herd" name="Deer"><file usable="red_deer_territories" /></territory>
</territories></env>
XML);
        $this->seedFile($project, $user, 'wild_boar_territories.xml', <<<'XML'
<territory-type><territory color="1"><zone name="Graze" smin="0" smax="5" dmin="0" dmax="5" x="1" z="2" r="60"/></territory></territory-type>
XML);
        $import = ConfigurationImport::query()->where('project_id', $project->id)
            ->where('original_filename', 'wild_boar_territories.xml')->firstOrFail();
        $storagePath = $import->storage_path;
        $this->assertTrue(Storage::disk('dayz')->exists($storagePath));

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnValidationWarnings')
            ->call('deleteUnregisteredTerritoryFile', 'wild_boar_territories.xml')
            ->assertSet('spawnValidationWarnings', fn (array $warnings): bool => ! collect($warnings)->contains(fn (array $warning) => ($warning['unregistered'] ?? false) === true));

        $this->assertModelMissing($import);
        $this->assertDatabaseMissing('configuration_revisions', ['project_id' => $project->id, 'configuration_import_id' => $import->id]);
        Storage::disk('dayz')->assertMissing($storagePath);
    }
}
