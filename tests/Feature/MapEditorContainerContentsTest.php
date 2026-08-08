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

class MapEditorContainerContentsTest extends TestCase
{
    use RefreshDatabase;

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

        $this->seedFile($project, $user, 'cfgeventspawns.xml', <<<'XML'
<eventposdef>
    <event name="StaticTestWeaponsChest_DEV2">
        <pos x="100" z="200" a="0"/>
    </event>
</eventposdef>
XML);

        $this->seedFile($project, $user, 'events.xml', <<<'XML'
<events>
    <event name="StaticTestWeaponsChest_DEV2">
        <nominal>1</nominal>
        <min>1</min>
        <max>1</max>
        <lifetime>3600</lifetime>
        <restock>0</restock>
        <saferadius>100</saferadius>
        <distanceradius>100</distanceradius>
        <cleanupradius>100</cleanupradius>
        <flags deletable="0" init_random="0" remove_damaged="0"/>
        <position>fixed</position>
        <limit>unlimited</limit>
        <active>1</active>
        <children>
            <child lootmax="0" lootmin="0" max="1" min="1" type="Barrel_Green"/>
        </children>
    </event>
</events>
XML);

        $this->seedFile($project, $user, 'cfgspawnabletypes.xml', <<<'XML'
<spawnabletypes>
    <type name="Barrel_Green">
        <hoarder/>
        <cargo chance="1.00">
            <item name="Mag_STANAG_30Rnd" chance="1.00" quantmin="100" quantmax="100"/>
        </cargo>
    </type>
</spawnabletypes>
XML);

        return [$user, $project];
    }

    public function test_a_container_events_cargo_and_hoarder_load_into_the_map_point(): void
    {
        [$user, $project] = $this->seedProject();

        $component = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadMarkers');

        $markers = collect($component->get('markers'));
        $marker = $markers->firstWhere('label', 'StaticTestWeaponsChest_DEV2');

        $this->assertNotNull($marker, 'Expected a marker for the StaticTestWeaponsChest_DEV2 event.');
        $this->assertTrue($marker['parameters']['hoarder']);
        $this->assertSame([
            ['name' => 'Mag_STANAG_30Rnd', 'chance' => 1.0, 'quantmin' => 100, 'quantmax' => 100],
        ], $marker['parameters']['cargo_items']);
    }

    public function test_saving_a_point_with_cargo_items_writes_quantmin_and_quantmax_to_cfgspawnabletypes(): void
    {
        [$user, $project] = $this->seedProject();

        $eventSpawnsRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgeventspawns.xml');

        $response = $this->actingAs($user)->postJson(route('map-editor.points.update'), [
            'project_id' => $project->id,
            'revision_id' => $eventSpawnsRevision->id,
            'filename' => 'cfgeventspawns.xml',
            'label' => 'StaticTestWeaponsChest_DEV2',
            'path' => '/eventposdef/event[1]/pos[1]',
            'x' => 100,
            'z' => 200,
            'new_x' => 100,
            'new_z' => 200,
            'parameters' => [
                'orientation' => 0,
                'cargo_items' => json_encode([
                    ['name' => 'AKM', 'chance' => 1, 'quantmin' => '', 'quantmax' => ''],
                    ['name' => 'Mag_STANAG_30Rnd', 'chance' => 1, 'quantmin' => 5, 'quantmax' => 5],
                ]),
                'hoarder' => true,
            ],
        ]);

        $response->assertOk();

        $latestSpawnable = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'cfgspawnabletypes.xml');

        $this->assertNotNull($latestSpawnable);
        $content = Storage::disk('dayz')->get($latestSpawnable->storage_path);
        $this->assertStringContainsString('name="AKM"', $content);
        $this->assertStringContainsString('name="Mag_STANAG_30Rnd"', $content);
        $this->assertStringContainsString('quantmin="5"', $content);
        $this->assertStringContainsString('quantmax="5"', $content);
        $this->assertStringNotContainsString('quantmin="0" quantmax="0" name="AKM"', $content);
        $this->assertStringContainsString('<hoarder', $content);
    }

    public function test_saving_a_point_without_touching_cargo_leaves_cfgspawnabletypes_unrelated_types_untouched(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnableRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgspawnabletypes.xml');
        Storage::disk('dayz')->put($spawnableRevision->storage_path, str_replace(
            '</spawnabletypes>',
            '<type name="Unrelated_Item"><cargo chance="1.00"><item name="Rope" chance="1.00"/></cargo></type></spawnabletypes>',
            Storage::disk('dayz')->get($spawnableRevision->storage_path)
        ));

        $eventSpawnsRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgeventspawns.xml');

        $response = $this->actingAs($user)->postJson(route('map-editor.points.update'), [
            'project_id' => $project->id,
            'revision_id' => $eventSpawnsRevision->id,
            'filename' => 'cfgeventspawns.xml',
            'label' => 'StaticTestWeaponsChest_DEV2',
            'path' => '/eventposdef/event[1]/pos[1]',
            'x' => 100,
            'z' => 200,
            'new_x' => 150,
            'new_z' => 250,
            'parameters' => [
                'orientation' => 0,
                'cargo_items' => json_encode([
                    ['name' => 'AKM', 'chance' => 1, 'quantmin' => 1, 'quantmax' => 1],
                ]),
                'hoarder' => true,
            ],
        ]);

        $response->assertOk();

        $latestSpawnable = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'cfgspawnabletypes.xml');
        $content = Storage::disk('dayz')->get($latestSpawnable->storage_path);
        $this->assertStringContainsString('name="Unrelated_Item"', $content);
        $this->assertStringContainsString('name="Rope"', $content);
        $this->assertStringContainsString('name="AKM"', $content);
    }
}
