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
        <limit>child</limit>
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
        // The point-edit modal needs this to show "this point spawns Barrel_Green" —
        // otherwise there's nothing on screen saying what kind of container it is.
        $this->assertSame(['Barrel_Green'], $marker['spawn_classnames']);
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

    public function test_a_cargo_item_with_no_chance_filled_in_defaults_to_always_guaranteed_not_never(): void
    {
        [$user, $project] = $this->seedProject();

        $eventSpawnsRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgeventspawns.xml');

        // Mirrors what the browser actually sends when the "šance" field is left empty:
        // the key is present with an empty string, not missing/null.
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
                    ['name' => 'Rope', 'chance' => '', 'quantmin' => '', 'quantmax' => ''],
                ]),
            ],
        ]);

        $response->assertOk();

        $latestSpawnable = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'cfgspawnabletypes.xml');
        $content = Storage::disk('dayz')->get($latestSpawnable->storage_path);
        $this->assertStringContainsString('name="Rope" chance="1"', $content);
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

    public function test_a_type_with_both_a_preset_group_and_an_items_group_only_loads_the_items_not_the_preset(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnableRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgspawnabletypes.xml');
        Storage::disk('dayz')->put($spawnableRevision->storage_path, <<<'XML'
<spawnabletypes>
    <type name="Barrel_Green">
        <hoarder/>
        <cargo chance="1.00" preset="AmmoPreset"/>
        <cargo chance="1.00"><item name="TacticalBaconCan" chance="0.80"/></cargo>
    </type>
</spawnabletypes>
XML);

        $component = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadMarkers');
        $marker = collect($component->get('markers'))->firstWhere('label', 'StaticTestWeaponsChest_DEV2');

        // The field's own help text says the preset is "used only if no items above are
        // specified" — loading both at once (as the raw XML technically allows) breaks that
        // contract and is what let a stale preset value silently reappear once the items were
        // cleared and saved (reported as "deleting cargo items doesn't save").
        $this->assertSame('', $marker['parameters']['cargo_preset']);
        $this->assertSame([
            ['name' => 'TacticalBaconCan', 'chance' => 0.8, 'quantmin' => '', 'quantmax' => ''],
        ], $marker['parameters']['cargo_items']);
    }

    public function test_clearing_cargo_items_removes_cargo_entirely_even_when_a_preset_group_also_existed(): void
    {
        [$user, $project] = $this->seedProject();
        $spawnableRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgspawnabletypes.xml');
        Storage::disk('dayz')->put($spawnableRevision->storage_path, <<<'XML'
<spawnabletypes>
    <type name="Barrel_Green">
        <hoarder/>
        <cargo chance="1.00" preset="AmmoPreset"/>
        <cargo chance="1.00"><item name="TacticalBaconCan" chance="0.80"/></cargo>
    </type>
</spawnabletypes>
XML);
        $eventSpawnsRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'cfgeventspawns.xml');

        // Simulates the actual reported steps: open the point (which now loads cargo_preset=''
        // per the fix above), remove the one item row, save with an empty cargo_items and the
        // preset field left exactly as loaded (empty) — never re-populated by the user.
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
                'cargo_items' => json_encode([]),
                'cargo_preset' => '',
                'hoarder' => true,
            ],
        ]);

        $response->assertOk();

        $latestSpawnable = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'cfgspawnabletypes.xml');
        $content = Storage::disk('dayz')->get($latestSpawnable->storage_path);
        $this->assertStringNotContainsString('AmmoPreset', $content);
        $this->assertStringNotContainsString('TacticalBaconCan', $content);
        $this->assertStringNotContainsString('<cargo', $content);
    }

    public function test_saving_coordinates_does_not_fail_when_the_event_has_no_children_in_events_xml(): void
    {
        [$user, $project] = $this->seedProject();

        // Overwrite events.xml with a version of the event that has no <children> at all —
        // this used to hard-abort the whole point save with a 422 ("nemá žádnou spawnovanou
        // child třídu"), even though eventFields always sends damage_min/cargo_preset/etc. on
        // every point save regardless of whether the user touched them.
        $eventsRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'events.xml');
        Storage::disk('dayz')->put($eventsRevision->storage_path, <<<'XML'
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
        <limit>child</limit>
        <active>1</active>
        <children/>
    </event>
</events>
XML);

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
            'new_x' => 300,
            'new_z' => 400,
            'parameters' => [
                'orientation' => 90,
                'cargo_items' => '',
                'hoarder' => false,
            ],
        ]);

        $response->assertOk();
        $response->assertJson(fn ($json) => $json->where('ok', true)->etc());
        $this->assertNotEmpty($response->json('warning'));

        $latestEventSpawns = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'cfgeventspawns.xml');
        $content = Storage::disk('dayz')->get($latestEventSpawns->storage_path);
        $this->assertStringContainsString('x="300"', $content);
        $this->assertStringContainsString('z="400"', $content);
    }

    public function test_a_malformed_events_xml_produces_a_distinct_warning_instead_of_looking_like_no_children(): void
    {
        [$user, $project] = $this->seedProject();

        $eventsRevision = $project->revisions()->with('configurationImport')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport->original_filename)) === 'events.xml');
        // A single bad token anywhere in a large production events.xml makes
        // simplexml_load_string() fail for the WHOLE file — even though the
        // StaticTestWeaponsChest_DEV2 event itself is perfectly correct.
        Storage::disk('dayz')->put($eventsRevision->storage_path, '<events><event name="Broken">&invalid;</events>');

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
            'parameters' => ['orientation' => 0, 'cargo_items' => '', 'hoarder' => false],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('nepodařilo naparsovat jako XML', $response->json('warning'));
    }

    public function test_the_events_xml_settings_load_into_the_map_point_for_editing(): void
    {
        [$user, $project] = $this->seedProject();

        $component = Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadMarkers');

        $marker = collect($component->get('markers'))->firstWhere('label', 'StaticTestWeaponsChest_DEV2');

        $this->assertNotNull($marker);
        $this->assertSame(1, $marker['parameters']['event_nominal']);
        $this->assertSame(1, $marker['parameters']['event_min']);
        $this->assertSame(1, $marker['parameters']['event_max']);
        $this->assertSame(3600, $marker['parameters']['event_lifetime']);
        $this->assertSame('fixed', $marker['parameters']['event_position']);
        $this->assertSame('child', $marker['parameters']['event_limit']);
        $this->assertTrue($marker['parameters']['event_active']);
        $this->assertFalse($marker['parameters']['event_deletable']);
        $this->assertSame('Barrel_Green', $marker['parameters']['event_classname']);
    }

    public function test_saving_a_point_with_event_settings_writes_them_to_events_xml(): void
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
                'event_nominal' => 3,
                'event_min' => 1,
                'event_max' => 3,
                'event_lifetime' => 7200,
                'event_restock' => 60,
                'event_saferadius' => 50,
                'event_distanceradius' => 50,
                'event_cleanupradius' => 50,
                'event_position' => 'player',
                'event_limit' => 'parent',
                'event_active' => false,
                'event_deletable' => true,
                'event_init_random' => true,
                'event_remove_damaged' => true,
            ],
        ]);

        $response->assertOk();
        $this->assertNull($response->json('warning'));

        $latestEvents = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'events.xml');
        $content = Storage::disk('dayz')->get($latestEvents->storage_path);

        $this->assertStringContainsString('<nominal>3</nominal>', $content);
        $this->assertStringContainsString('<lifetime>7200</lifetime>', $content);
        $this->assertStringContainsString('<position>player</position>', $content);
        $this->assertStringContainsString('<limit>parent</limit>', $content);
        $this->assertStringContainsString('<active>0</active>', $content);
        $this->assertStringContainsString('remove_damaged="1"', $content);
    }

    public function test_changing_the_event_classname_renames_the_child_and_resyncs_cfgspawnabletypes_to_the_new_name(): void
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
                'event_classname' => 'SeaChest',
                'hoarder' => true,
            ],
        ]);

        $response->assertOk();
        $this->assertNull($response->json('warning'));

        $latestEvents = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'events.xml');
        $eventsContent = Storage::disk('dayz')->get($latestEvents->storage_path);
        $this->assertStringContainsString('type="SeaChest"', $eventsContent);
        $this->assertStringNotContainsString('type="Barrel_Green"', $eventsContent);

        // The cfgspawnabletypes sync re-reads the just-saved events.xml, so hoarder=true from
        // the SAME request should land on the NEW classname, not the old one.
        $latestSpawnable = $project->revisions()->with('configurationImport')->orderByDesc('revision_number')->get()
            ->first(fn ($revision) => strtolower(basename($revision->configurationImport?->original_filename ?? $revision->storage_path)) === 'cfgspawnabletypes.xml');
        $spawnableContent = Storage::disk('dayz')->get($latestSpawnable->storage_path);
        $this->assertStringContainsString('<type name="SeaChest">', $spawnableContent);
        $this->assertStringContainsString('<hoarder/>', $spawnableContent);
    }
}
