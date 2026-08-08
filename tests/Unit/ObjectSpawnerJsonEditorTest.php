<?php

namespace Tests\Unit;

use App\Services\Revision\ObjectSpawnerJsonEditor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ObjectSpawnerJsonEditorTest extends TestCase
{
    private const SAMPLE = <<<'JSON'
{
    "SomeUnrelatedTopLevelKey": "must survive every write",
    "Objects": [
        {
            "name": "Land_Castle_Bastion",
            "pos": [7500.5, 12.0, 8200.25],
            "ypr": [90.0, 0.0, 0.0],
            "scale": 1.0,
            "enableCEPersistency": true
        }
    ]
}
JSON;

    public function test_supports_matches_spawner_filename_pattern(): void
    {
        $editor = new ObjectSpawnerJsonEditor();

        $this->assertTrue($editor->supports('custom/military.json', self::SAMPLE));
        $this->assertTrue($editor->supports('custom/spawnerData.json', self::SAMPLE));
        $this->assertFalse($editor->supports('custom/startovni-vybava.json', '{"PlayerData":{}}'));
    }

    public function test_supports_also_recognizes_content_with_an_objects_array_regardless_of_filename(): void
    {
        $editor = new ObjectSpawnerJsonEditor();

        $this->assertTrue($editor->supports('custom/base.json', self::SAMPLE));
    }

    public function test_entries_lists_every_object_with_its_index_based_path(): void
    {
        $entries = (new ObjectSpawnerJsonEditor())->entries(self::SAMPLE);

        $this->assertCount(1, $entries);
        $this->assertSame('Objects[0]', $entries[0]['path']);
        $this->assertSame('Land_Castle_Bastion', $entries[0]['name']);
        $this->assertSame([7500.5, 12.0, 8200.25], $entries[0]['pos']);
        $this->assertSame([90.0, 0.0, 0.0], $entries[0]['ypr']);
        $this->assertTrue($entries[0]['enableCEPersistency']);
    }

    public function test_append_adds_a_new_object_and_preserves_every_other_key(): void
    {
        $result = (new ObjectSpawnerJsonEditor())->append(self::SAMPLE, [
            'name' => 'Land_Wall_Gate_FenR_Big',
            'pos' => [100, 0, 200],
            'ypr' => [45, 0, 0],
            'scale' => 1,
            'enableCEPersistency' => false,
        ]);
        $decoded = json_decode($result, true);

        $this->assertSame('must survive every write', $decoded['SomeUnrelatedTopLevelKey']);
        $this->assertCount(2, $decoded['Objects']);
        $this->assertSame('Land_Castle_Bastion', $decoded['Objects'][0]['name']);
        $this->assertSame('Land_Wall_Gate_FenR_Big', $decoded['Objects'][1]['name']);
    }

    public function test_append_normalizes_a_missing_scale_and_persistency_to_sane_defaults(): void
    {
        $result = (new ObjectSpawnerJsonEditor())->append('{"Objects": []}', [
            'name' => 'Barrel_Green',
            'pos' => [1, 2, 3],
            'ypr' => [0, 0, 0],
        ]);
        $decoded = json_decode($result, true);

        // json_encode drops the trailing ".0" from whole-number floats, so a round-tripped
        // 1.0 decodes back as PHP int 1 — assertEquals (loose) is correct here, not assertSame.
        $this->assertEquals(1, $decoded['Objects'][0]['scale']);
        $this->assertFalse($decoded['Objects'][0]['enableCEPersistency']);
    }

    public function test_update_at_changes_only_the_targeted_object(): void
    {
        $twoObjects = (new ObjectSpawnerJsonEditor())->append(self::SAMPLE, [
            'name' => 'Land_Wall_Gate_FenR_Big', 'pos' => [1, 1, 1], 'ypr' => [0, 0, 0],
        ]);

        $result = (new ObjectSpawnerJsonEditor())->updateAt($twoObjects, 1, ['ypr' => [180, 0, 0]]);
        $decoded = json_decode($result, true);

        $this->assertEquals([90, 0, 0], $decoded['Objects'][0]['ypr']);
        $this->assertEquals([180, 0, 0], $decoded['Objects'][1]['ypr']);
        $this->assertSame('Land_Wall_Gate_FenR_Big', $decoded['Objects'][1]['name']);
    }

    public function test_update_at_an_out_of_range_index_throws(): void
    {
        $this->expectException(RuntimeException::class);
        (new ObjectSpawnerJsonEditor())->updateAt(self::SAMPLE, 5, ['scale' => 2]);
    }

    public function test_remove_at_deletes_and_reindexes(): void
    {
        $twoObjects = (new ObjectSpawnerJsonEditor())->append(self::SAMPLE, [
            'name' => 'Land_Wall_Gate_FenR_Big', 'pos' => [1, 1, 1], 'ypr' => [0, 0, 0],
        ]);

        $result = (new ObjectSpawnerJsonEditor())->removeAt($twoObjects, 0);
        $decoded = json_decode($result, true);

        $this->assertCount(1, $decoded['Objects']);
        $this->assertSame('Land_Wall_Gate_FenR_Big', $decoded['Objects'][0]['name']);
    }

    public function test_remove_many_deletes_every_targeted_index_without_shifting_bugs(): void
    {
        $editor = new ObjectSpawnerJsonEditor();
        $content = $editor->append($editor->append($editor->append(self::SAMPLE, [
            'name' => 'B', 'pos' => [1, 1, 1], 'ypr' => [0, 0, 0],
        ]), [
            'name' => 'C', 'pos' => [2, 2, 2], 'ypr' => [0, 0, 0],
        ]), [
            'name' => 'D', 'pos' => [3, 3, 3], 'ypr' => [0, 0, 0],
        ]);
        // 4 objects: 0=Land_Castle_Bastion, 1=B, 2=C, 3=D — remove 0 and 2 (Bastion and C).

        $result = $editor->removeMany($content, [0, 2]);
        $decoded = json_decode($result, true);

        $this->assertSame(['B', 'D'], array_column($decoded['Objects'], 'name'));
    }

    public function test_duplicate_at_inserts_a_copy_right_after_the_original(): void
    {
        $result = (new ObjectSpawnerJsonEditor())->duplicateAt(self::SAMPLE, 0);
        $decoded = json_decode($result, true);

        $this->assertCount(2, $decoded['Objects']);
        $this->assertSame('Land_Castle_Bastion', $decoded['Objects'][0]['name']);
        $this->assertSame('Land_Castle_Bastion', $decoded['Objects'][1]['name']);
        $this->assertSame($decoded['Objects'][0]['pos'], $decoded['Objects'][1]['pos']);
    }

    public function test_index_from_path_parses_the_objects_bracket_notation(): void
    {
        $editor = new ObjectSpawnerJsonEditor();

        $this->assertSame(3, $editor->indexFromPath('Objects[3]'));
    }

    public function test_index_from_path_rejects_anything_else(): void
    {
        $this->expectException(RuntimeException::class);
        (new ObjectSpawnerJsonEditor())->indexFromPath('/eventposdef/event[1]/pos[1]');
    }

    public function test_validate_accepts_a_well_formed_object(): void
    {
        $errors = (new ObjectSpawnerJsonEditor())->validate([
            'name' => 'Land_Castle_Bastion', 'pos' => [100, 0, 200], 'ypr' => [0, 0, 0], 'scale' => 1,
        ]);

        $this->assertSame([], $errors);
    }

    public function test_validate_rejects_a_missing_classname(): void
    {
        $errors = (new ObjectSpawnerJsonEditor())->validate(['pos' => [100, 0, 200], 'ypr' => [0, 0, 0]]);

        $this->assertNotEmpty($errors);
    }

    public function test_validate_rejects_an_invalid_classname(): void
    {
        $errors = (new ObjectSpawnerJsonEditor())->validate(['name' => 'not a classname!', 'pos' => [1, 1, 1], 'ypr' => [0, 0, 0]]);

        $this->assertNotEmpty($errors);
    }

    public function test_validate_rejects_coordinates_outside_the_map(): void
    {
        $errors = (new ObjectSpawnerJsonEditor())->validate(['name' => 'X', 'pos' => [99999, 0, 200], 'ypr' => [0, 0, 0]]);

        $this->assertNotEmpty($errors);
    }

    public function test_validate_rejects_a_non_positive_scale(): void
    {
        $errors = (new ObjectSpawnerJsonEditor())->validate(['name' => 'X', 'pos' => [1, 1, 1], 'ypr' => [0, 0, 0], 'scale' => 0]);

        $this->assertNotEmpty($errors);
    }

    public function test_register_spawner_file_creates_worlds_data_array_when_none_exists(): void
    {
        $result = (new ObjectSpawnerJsonEditor())->registerSpawnerFile('{"SomeKey": true}', 'custom/military.json');
        $decoded = json_decode($result, true);

        $this->assertSame(['custom/military.json'], $decoded['WorldsData']['objectSpawnersArr']);
        $this->assertTrue($decoded['SomeKey']);
    }

    public function test_register_spawner_file_uses_the_already_present_worlds_data_location(): void
    {
        $gameplay = '{"WorldsData": {"lightingConfig": 0, "objectSpawnersArr": ["custom/base.json"]}}';

        $result = (new ObjectSpawnerJsonEditor())->registerSpawnerFile($gameplay, 'custom/military.json');
        $decoded = json_decode($result, true);

        $this->assertSame(['custom/base.json', 'custom/military.json'], $decoded['WorldsData']['objectSpawnersArr']);
        $this->assertSame(0, $decoded['WorldsData']['lightingConfig']);
    }

    public function test_register_spawner_file_uses_an_existing_player_data_location_instead_of_worlds_data(): void
    {
        $gameplay = '{"PlayerData": {"objectSpawnersArr": []}}';

        $result = (new ObjectSpawnerJsonEditor())->registerSpawnerFile($gameplay, 'custom/military.json');
        $decoded = json_decode($result, true);

        $this->assertSame(['custom/military.json'], $decoded['PlayerData']['objectSpawnersArr']);
        $this->assertArrayNotHasKey('WorldsData', $decoded);
    }

    public function test_register_spawner_file_is_a_no_op_when_the_file_is_already_registered(): void
    {
        $gameplay = '{"WorldsData": {"objectSpawnersArr": ["custom/military.json"]}}';

        $result = (new ObjectSpawnerJsonEditor())->registerSpawnerFile($gameplay, 'custom/military.json');
        $decoded = json_decode($result, true);

        $this->assertSame(['custom/military.json'], $decoded['WorldsData']['objectSpawnersArr']);
    }

    public function test_register_spawner_file_matches_case_insensitively_with_normalized_slashes(): void
    {
        $gameplay = '{"WorldsData": {"objectSpawnersArr": ["Custom\\\\Military.json"]}}';

        $result = (new ObjectSpawnerJsonEditor())->registerSpawnerFile($gameplay, 'custom/military.json');
        $decoded = json_decode($result, true);

        $this->assertCount(1, $decoded['WorldsData']['objectSpawnersArr']);
    }
}
