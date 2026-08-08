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

class MapEditorObjectSpawnerTest extends TestCase
{
    use RefreshDatabase;

    private function seedFile(Project $project, User $user, string $filename, string $content): ConfigurationRevision
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

        return ConfigurationRevision::query()->create([
            'project_id' => $project->id,
            'revision_number' => $project->revisions()->count() + 1,
            'configuration_import_id' => $import->id,
            'storage_path' => $path,
            'sha256' => $sha256,
            'change_summary' => 'test',
            'created_by' => $user->id,
        ]);
    }

    /** @return array{0: User, 1: Project} */
    private function seedProject(): array
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);

        return [$user, $project];
    }

    private const SPAWNER_SAMPLE = <<<'JSON'
{
    "Objects": [
        {"name": "Land_Castle_Bastion", "pos": [7500.0, 12.0, 8200.0], "ypr": [90.0, 0.0, 0.0], "scale": 1.0, "enableCEPersistency": true}
    ]
}
JSON;

    public function test_existing_object_spawner_json_loads_as_editable_markers(): void
    {
        [$user, $project] = $this->seedProject();
        $this->seedFile($project, $user, 'custom/base.json', self::SPAWNER_SAMPLE);

        $component = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadMarkers');

        $markers = collect($component->get('markers'));
        $marker = $markers->firstWhere('type', 'object-spawner');

        $this->assertNotNull($marker);
        $this->assertTrue($marker['editable']);
        $this->assertSame('Land_Castle_Bastion', $marker['label']);
        $this->assertSame('Land_Castle_Bastion', $marker['parameters']['classname']);
        $this->assertSame('Castle', $marker['parameters']['catalog_category']);
        $this->assertTrue($marker['parameters']['catalog_known']);
    }

    public function test_an_unknown_classname_still_produces_an_editable_marker(): void
    {
        [$user, $project] = $this->seedProject();
        $this->seedFile($project, $user, 'custom/base.json', '{"Objects": [{"name": "ThisClassnameDoesNotExist_XYZ", "pos": [1, 0, 2], "ypr": [0, 0, 0]}]}');

        $component = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadMarkers');

        $marker = collect($component->get('markers'))->firstWhere('type', 'object-spawner');

        $this->assertNotNull($marker);
        $this->assertTrue($marker['editable']);
        $this->assertFalse($marker['parameters']['catalog_known']);
        $this->assertStringContainsString('Unknown Object', $marker['help']);
    }

    public function test_saving_a_new_object_appends_to_an_existing_file_and_registers_in_cfggameplay(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnerRevision = $this->seedFile($project, $user, 'custom/base.json', '{"Objects": []}');
        $this->seedFile($project, $user, 'cfggameplay.json', '{"WorldsData": {"objectSpawnersArr": []}}');

        $response = $this->actingAs($user)->postJson(route('map-editor.points.store'), [
            'project_id' => $project->id,
            'type' => 'custom',
            'label' => 'Land_Castle_Bastion',
            'target_filename' => 'custom/base.json',
            'x' => 100, 'z' => 200,
            'parameters' => ['classname' => 'Land_Castle_Bastion', 'height' => 5, 'yaw' => 45, 'scale' => 1, 'enable_ce_persistency' => true],
        ]);

        $response->assertOk();
        $this->assertNull($response->json('warning'));

        $spawnerContent = json_decode(Storage::disk('dayz')->get(
            $project->revisions()->latest('revision_number')->get()->first(fn ($r) => strtolower(basename($r->configurationImport?->original_filename ?? '')) === 'base.json')->storage_path
        ), true);
        $this->assertCount(1, $spawnerContent['Objects']);
        $this->assertSame('Land_Castle_Bastion', $spawnerContent['Objects'][0]['name']);
        $this->assertEquals([100, 5, 200], $spawnerContent['Objects'][0]['pos']);
        $this->assertTrue($spawnerContent['Objects'][0]['enableCEPersistency']);

        $gameplayContent = json_decode(Storage::disk('dayz')->get(
            $project->revisions()->with('configurationImport')->get()->filter(fn ($r) => strtolower(basename($r->configurationImport?->original_filename ?? '')) === 'cfggameplay.json')->sortByDesc('revision_number')->first()->storage_path
        ), true);
        $this->assertSame(['custom/base.json'], $gameplayContent['WorldsData']['objectSpawnersArr']);
    }

    public function test_saving_the_very_first_object_creates_its_target_file_and_registers_it(): void
    {
        [$user, $project] = $this->seedProject();
        $this->seedFile($project, $user, 'cfggameplay.json', '{"WorldsData": {"objectSpawnersArr": []}}');

        $response = $this->actingAs($user)->postJson(route('map-editor.points.store'), [
            'project_id' => $project->id,
            'type' => 'custom',
            'label' => 'Barrel_Green',
            'target_filename' => 'custom/military.json',
            'x' => 100, 'z' => 200,
            'parameters' => ['classname' => 'Barrel_Green'],
        ]);

        $response->assertOk();
        $revisions = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get();
        $created = $revisions->first(fn ($r) => strtolower(str_replace('\\', '/', $r->configurationImport?->original_filename ?? '')) === 'custom/military.json');
        $this->assertNotNull($created, 'Expected custom/military.json to have been created.');
        $content = json_decode(Storage::disk('dayz')->get($created->storage_path), true);
        $this->assertSame('Barrel_Green', $content['Objects'][0]['name']);

        $gameplayContent = json_decode(Storage::disk('dayz')->get(
            $revisions->filter(fn ($r) => strtolower(basename($r->configurationImport?->original_filename ?? '')) === 'cfggameplay.json')->sortByDesc('revision_number')->first()->storage_path
        ), true);
        $this->assertContains('custom/military.json', $gameplayContent['WorldsData']['objectSpawnersArr']);
    }

    public function test_saving_rejects_an_invalid_target_filename(): void
    {
        [$user, $project] = $this->seedProject();

        $response = $this->actingAs($user)->postJson(route('map-editor.points.store'), [
            'project_id' => $project->id,
            'type' => 'custom',
            'label' => 'Barrel_Green',
            'target_filename' => 'events.xml',
            'x' => 100, 'z' => 200,
            'parameters' => ['classname' => 'Barrel_Green'],
        ]);

        $response->assertStatus(422);
    }

    public function test_saving_rejects_an_invalid_classname(): void
    {
        [$user, $project] = $this->seedProject();
        $this->seedFile($project, $user, 'custom/base.json', '{"Objects": []}');

        $response = $this->actingAs($user)->postJson(route('map-editor.points.store'), [
            'project_id' => $project->id,
            'type' => 'custom',
            'label' => 'x',
            'target_filename' => 'custom/base.json',
            'x' => 100, 'z' => 200,
            'parameters' => ['classname' => 'not a valid classname!'],
        ]);

        $response->assertStatus(422);
    }

    public function test_updating_an_object_changes_its_position_and_rotation(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnerRevision = $this->seedFile($project, $user, 'custom/base.json', self::SPAWNER_SAMPLE);

        $response = $this->actingAs($user)->postJson(route('map-editor.points.update'), [
            'project_id' => $project->id,
            'revision_id' => $spawnerRevision->id,
            'filename' => 'custom/base.json',
            'label' => 'Land_Castle_Bastion',
            'path' => 'Objects[0]',
            'x' => 7500, 'z' => 8200,
            'new_x' => 8000, 'new_z' => 9000,
            'parameters' => ['classname' => 'Land_Castle_Bastion', 'height' => 20, 'yaw' => 180, 'pitch' => 0, 'roll' => 0, 'scale' => 2, 'enable_ce_persistency' => false],
        ]);

        $response->assertOk();
        $latest = $project->revisions()->latest('revision_number')->first();
        $content = json_decode(Storage::disk('dayz')->get($latest->storage_path), true);
        // json_encode drops the trailing ".0" from whole-number floats, so these round-trip
        // back as PHP ints — assertEquals (loose) is correct here, not assertSame.
        $this->assertEquals([8000, 20, 9000], $content['Objects'][0]['pos']);
        $this->assertEquals([180, 0, 0], $content['Objects'][0]['ypr']);
        $this->assertEquals(2, $content['Objects'][0]['scale']);
        $this->assertFalse($content['Objects'][0]['enableCEPersistency']);
    }

    public function test_deleting_an_object_removes_it(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnerRevision = $this->seedFile($project, $user, 'custom/base.json', self::SPAWNER_SAMPLE);

        $response = $this->actingAs($user)->postJson(route('map-editor.points.delete'), [
            'project_id' => $project->id,
            'revision_id' => $spawnerRevision->id,
            'filename' => 'custom/base.json',
            'path' => 'Objects[0]',
            'x' => 7500, 'z' => 8200,
        ]);

        $response->assertOk();
        $latest = $project->revisions()->latest('revision_number')->first();
        $content = json_decode(Storage::disk('dayz')->get($latest->storage_path), true);
        $this->assertSame([], $content['Objects']);
    }

    public function test_duplicating_an_object_adds_a_copy(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnerRevision = $this->seedFile($project, $user, 'custom/base.json', self::SPAWNER_SAMPLE);

        $response = $this->actingAs($user)->postJson(route('map-editor.points.duplicate'), [
            'project_id' => $project->id,
            'revision_id' => $spawnerRevision->id,
            'filename' => 'custom/base.json',
            'path' => 'Objects[0]',
        ]);

        $response->assertOk();
        $latest = $project->revisions()->latest('revision_number')->first();
        $content = json_decode(Storage::disk('dayz')->get($latest->storage_path), true);
        $this->assertCount(2, $content['Objects']);
        $this->assertSame('Land_Castle_Bastion', $content['Objects'][1]['name']);
    }

    public function test_bulk_deleting_removes_every_targeted_object_regardless_of_order(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnerRevision = $this->seedFile($project, $user, 'custom/base.json', '{"Objects": [
            {"name": "A", "pos": [1, 0, 1], "ypr": [0, 0, 0]},
            {"name": "B", "pos": [2, 0, 2], "ypr": [0, 0, 0]},
            {"name": "C", "pos": [3, 0, 3], "ypr": [0, 0, 0]}
        ]}');

        $response = $this->actingAs($user)->postJson(route('map-editor.points.bulk-delete'), [
            'project_id' => $project->id,
            'revision_id' => $spawnerRevision->id,
            'filename' => 'custom/base.json',
            'scopes' => ['Objects[0]', 'Objects[2]'],
        ]);

        $response->assertOk();
        $latest = $project->revisions()->latest('revision_number')->first();
        $content = json_decode(Storage::disk('dayz')->get($latest->storage_path), true);
        $this->assertSame(['B'], array_column($content['Objects'], 'name'));
    }

    public function test_the_object_catalog_endpoint_returns_the_full_catalog_for_lazy_loading(): void
    {
        [$user] = $this->seedProject();

        $response = $this->actingAs($user)->getJson(route('map-editor.object-catalog'));

        $response->assertOk();
        $this->assertTrue(collect($response->json())->contains(fn (array $e) => $e['classname'] === 'Land_Castle_Bastion'));
    }

    public function test_the_object_catalog_endpoint_requires_authentication(): void
    {
        $this->getJson(route('map-editor.object-catalog'))->assertUnauthorized();
    }
}
