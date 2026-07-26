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
}
