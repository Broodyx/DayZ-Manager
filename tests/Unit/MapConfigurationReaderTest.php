<?php

namespace Tests\Unit;

use App\Services\Dayz\MapConfigurationEditor;
use App\Services\Dayz\MapConfigurationReader;
use PHPUnit\Framework\TestCase;

class MapConfigurationReaderTest extends TestCase
{
    public function test_player_spawn_positions_are_generator_areas_with_unmodified_world_coordinates(): void
    {
        $xml = <<<'XML'
        <playerspawnpoints><fresh>
          <generator_params><grid_width>200</grid_width><grid_height>160</grid_height></generator_params>
          <generator_posbubbles><group name="South coast"><pos x="6063.018" z="1931.907"/></group></generator_posbubbles>
        </fresh></playerspawnpoints>
        XML;

        $markers = (new MapConfigurationReader())->markers('cfgplayerspawnpoints.xml', $xml);

        $this->assertCount(1, $markers);
        $this->assertSame(6063.018, $markers[0]['worldX']);
        $this->assertSame(1931.907, $markers[0]['worldZ']);
        $this->assertSame(100.0, $markers[0]['radius']);
        $this->assertSame('player-spawn-area', $markers[0]['type']);
        $this->assertStringContainsString('nejde o přesný spawn bod', $markers[0]['help']);
    }

    public function test_prototype_files_do_not_create_false_world_markers(): void
    {
        $xml = '<prototype><point x="100" z="200"/></prototype>';

        $this->assertSame([], (new MapConfigurationReader())->markers('mapclusterproto.xml', $xml));
    }

    public function test_map_group_pos_uses_first_and_third_vector_components_as_x_and_z(): void
    {
        $xml = '<map><group name="Land_Test" pos="80.25 113.79 4422.18"/></map>';

        $markers = (new MapConfigurationReader())->markers('mapgrouppos.xml', $xml);

        $this->assertSame(80.25, $markers[0]['worldX']);
        $this->assertSame(4422.18, $markers[0]['worldZ']);
    }

    public function test_editor_updates_map_group_coordinates_and_preserves_height(): void
    {
        $xml = '<map><group name="Land_Test" pos="80.25 113.79 4422.18"/></map>';

        $updated = (new MapConfigurationEditor())->updateCoordinates(
            'mapgrouppos.xml',
            $xml,
            '/map/group[1]',
            1000,
            2000,
        );

        $this->assertStringContainsString('pos="1000 113.79 2000"', $updated);
    }

    public function test_editor_can_add_and_remove_player_spawn_area(): void
    {
        $xml = '<playerspawnpoints><fresh><generator_posbubbles/></fresh></playerspawnpoints>';
        $editor = new MapConfigurationEditor();
        $updated = $editor->appendPlayerSpawnArea($xml, 'Test group', 1200, 3400);
        $markers = (new MapConfigurationReader())->markers('cfgplayerspawnpoints.xml', $updated);

        $this->assertCount(1, $markers);
        $this->assertSame(1200.0, $markers[0]['worldX']);
        $this->assertSame(3400.0, $markers[0]['worldZ']);

        $deleted = $editor->delete($updated, $markers[0]['path']);
        $this->assertSame([], (new MapConfigurationReader())->markers('cfgplayerspawnpoints.xml', $deleted));
    }

    public function test_editor_appends_animal_territory_to_territory_xml(): void
    {
        $xml = '<?xml version="1.0"?><territory-type><territory color="1"><zone name="Rest" smin="0" smax="0" dmin="0" dmax="0" x="10" z="20" r="30"/></territory></territory-type>';

        $updated = (new MapConfigurationEditor())->appendTerritoryZone($xml, 'HuntingGround', 1200, 3400, 150);

        $this->assertStringContainsString('name="HuntingGround"', $updated);
        $this->assertStringContainsString('x="1200"', $updated);
        $this->assertStringContainsString('z="3400"', $updated);
        $this->assertStringContainsString('r="150"', $updated);
    }

    public function test_editor_appends_group_to_mapgrouppos_xml(): void
    {
        $updated = (new MapConfigurationEditor())->appendMapGroup(
            '<?xml version="1.0"?><map/>',
            'Land_Shed_W2',
            1200,
            3400,
        );

        $this->assertStringContainsString('name="Land_Shed_W2"', $updated);
        $this->assertStringContainsString('pos="1200 0 3400"', $updated);
    }

    public function test_editor_appends_contaminated_area_to_json(): void
    {
        $updated = (new MapConfigurationEditor())->appendContaminatedArea(
            '{"Areas":[]}',
            'Test zone',
            1200,
            3400,
            100,
        );
        $data = json_decode($updated, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Test zone', $data['Areas'][0]['AreaName']);
        $this->assertSame([1200.0, 0.0, 3400.0], array_map('floatval', $data['Areas'][0]['Data']['Pos']));
        $this->assertSame(100.0, (float) $data['Areas'][0]['Data']['Radius']);
    }

    public function test_editor_preserves_all_configured_territory_parameters(): void
    {
        $updated = (new MapConfigurationEditor())->appendTerritoryZone(
            '<territory-type/>',
            'Graze',
            1200,
            3400,
            ['radius' => 225, 'smin' => 1, 'smax' => 3, 'dmin' => 2, 'dmax' => 5],
        );

        $this->assertStringContainsString('name="Graze"', $updated);
        $this->assertStringContainsString('smin="1"', $updated);
        $this->assertStringContainsString('smax="3"', $updated);
        $this->assertStringContainsString('dmin="2"', $updated);
        $this->assertStringContainsString('dmax="5"', $updated);
        $this->assertStringContainsString('r="225"', $updated);
    }

    public function test_editor_writes_map_group_height_and_orientation(): void
    {
        $updated = (new MapConfigurationEditor())->appendMapGroup(
            '<map/>',
            'Land_Test',
            1200,
            3400,
            ['pos_y' => 12.5, 'pitch' => 1, 'yaw' => 90, 'roll' => -2, 'orientation' => 90],
        );

        $this->assertStringContainsString('pos="1200 12.5 3400"', $updated);
        $this->assertStringContainsString('rpy="1 90 -2"', $updated);
        $this->assertStringContainsString('a="90"', $updated);
    }

    public function test_editor_can_add_player_spawn_to_selected_mode(): void
    {
        $xml = '<playerspawnpoints><fresh><generator_posbubbles/></fresh><hop><generator_posbubbles/></hop></playerspawnpoints>';
        $updated = (new MapConfigurationEditor())->appendPlayerSpawnArea($xml, 'Hop group', 1200, 3400, 'hop');

        $this->assertStringContainsString('<hop>', $updated);
        $this->assertMatchesRegularExpression('/<hop>.*name="Hop group"/s', $updated);
        $this->assertDoesNotMatchRegularExpression('/<fresh>.*name="Hop group".*<\/fresh>/s', $updated);
    }

    public function test_editor_writes_complete_contaminated_area_fields(): void
    {
        $updated = (new MapConfigurationEditor())->appendContaminatedArea(
            '{"Areas":[]}',
            'Deep zone',
            1200,
            3400,
            [
                'radius' => 150, 'pos_y' => 8, 'pos_height' => 40, 'neg_height' => 12,
                'inner_part_dist' => 60, 'outer_offset' => 25, 'particle_name' => 'main',
                'around_particle' => 'around', 'tiny_particle' => 'tiny', 'ppe_type' => 'ppe',
            ],
        );
        $area = json_decode($updated, true, flags: JSON_THROW_ON_ERROR)['Areas'][0];

        $this->assertSame([1200.0, 8.0, 3400.0], array_map('floatval', $area['Data']['Pos']));
        $this->assertSame(40.0, (float) $area['Data']['PosHeight']);
        $this->assertSame(12.0, (float) $area['Data']['NegHeight']);
        $this->assertSame('main', $area['Data']['ParticleName']);
        $this->assertSame('around', $area['PlayerData']['AroundPartName']);
        $this->assertSame('tiny', $area['PlayerData']['TinyPartName']);
        $this->assertSame('ppe', $area['PlayerData']['PPERequesterType']);
    }
}
