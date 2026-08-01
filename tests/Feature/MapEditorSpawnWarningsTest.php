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
}
