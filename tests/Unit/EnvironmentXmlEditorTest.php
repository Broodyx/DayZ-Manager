<?php

namespace Tests\Unit;

use App\Services\Revision\EnvironmentXmlEditor;
use RuntimeException;
use Tests\TestCase;

class EnvironmentXmlEditorTest extends TestCase
{
    /** Trimmed but structurally faithful copy of a real cfgenvironment.xml export. */
    private string $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
<env>
	<territories>
		<file path="env/cattle_territories.xml" />
		<file path="env/sheep_goat_territories.xml" />
		<file path="env/red_deer_territories.xml" />
		<file path="env/wolf_territories.xml" />
		<file path="env/hen_territories.xml" />
		<file path="env/zombie_territories.xml" />

		<territory type="Herd" name="Deer" behavior="DZDeerGroupBeh">
			<file usable="red_deer_territories" />
		</territory>
		<territory type="Herd" name="Cow" behavior="DZdomesticGroupBeh">
			<file usable="cattle_territories" />
		</territory>
		<territory type="Herd" name="Goat" behavior="DZSheepGroupBeh">
			<file usable="sheep_goat_territories" />
		</territory>
		<territory type="Herd" name="Sheep" behavior="DZSheepGroupBeh">
			<file usable="sheep_goat_territories" />
		</territory>
		<territory type="Herd" name="Wolf" behavior="DZWolfGroupBeh">
			<file usable="wolf_territories" />
		</territory>

		<territory type="Ambient" name="AmbientHen" behavior="DZAmbientLifeGroupBeh">
			<file usable="hen_territories" />
			<agent type="Male" chance="1">
				<spawn configName="Animal_GallusGallusDomesticus" chance="1" />
			</agent>
			<agent type="Female" chance="3">
				<spawn configName="Animal_GallusGallusDomesticusF_Brown" chance="1" />
				<spawn configName="Animal_GallusGallusDomesticusF_Spotted" chance="10" />
			</agent>
			<item name="globalCountMax" val="50" />
			<item name="zoneCountMin" val="1" />
			<item name="zoneCountMax" val="1" />
			<item name="playerSpawnRadiusNear" val="25" />
			<item name="playerSpawnRadiusFar" val="75" />
		</territory>

		<territory type="Herd" name="ZombieTest" behavior="DZdomesticGroupBeh">
			<file usable="zombie_territories" />
			<agent type="Male">
				<spawn configName="ZombieMale3_NewAI" />
				<item name="countMin" val="0" />
				<item name="countMax" val="0" />
			</agent>
			<agent type="Female">
				<spawn configName="ZombieFemale3_NewAI" />
				<item name="countMin" val="0" />
				<item name="countMax" val="0" />
			</agent>
			<item name="herdsCount" val="0" />
		</territory>
	</territories>
</env>
XML;

    public function test_supports_requires_env_root_and_matching_filename(): void
    {
        $editor = app(EnvironmentXmlEditor::class);

        $this->assertTrue($editor->supports('cfgenvironment.xml', $this->xml));
        $this->assertFalse($editor->supports('cfgweather.xml', $this->xml));
        $this->assertFalse($editor->supports('cfgenvironment.xml', '<not-env/>'));
    }

    public function test_entries_returns_lightweight_summary_with_infected_flag(): void
    {
        $entries = app(EnvironmentXmlEditor::class)->entries($this->xml);
        $byName = collect($entries)->keyBy('name');

        $this->assertCount(7, $entries);
        $this->assertSame('red_deer_territories.xml', $byName['Deer']['file']);
        $this->assertFalse($byName['Deer']['is_infected']);
        $this->assertSame(2, $byName['AmbientHen']['agent_count']);
        $this->assertTrue($byName['ZombieTest']['is_infected']);
    }

    public function test_goat_and_sheep_both_resolve_to_the_shared_territory_file(): void
    {
        $entries = app(EnvironmentXmlEditor::class)->entries($this->xml);
        $byName = collect($entries)->keyBy('name');

        $this->assertSame('sheep_goat_territories.xml', $byName['Goat']['file']);
        $this->assertSame('sheep_goat_territories.xml', $byName['Sheep']['file']);
    }

    public function test_values_reads_full_agent_and_item_detail(): void
    {
        $values = app(EnvironmentXmlEditor::class)->values($this->xml, 'AmbientHen');

        $this->assertSame('Ambient', $values['type']);
        $this->assertSame('hen_territories.xml', $values['file']);
        $this->assertCount(2, $values['agents']);
        $this->assertSame('Male', $values['agents'][0]['type']);
        $this->assertSame('1', $values['agents'][0]['chance']);
        $this->assertSame('Animal_GallusGallusDomesticus', $values['agents'][0]['spawns'][0]['configName']);
        $this->assertCount(2, $values['agents'][1]['spawns']);
        $this->assertSame(
            ['globalCountMax' => '50', 'zoneCountMin' => '1', 'zoneCountMax' => '1', 'playerSpawnRadiusNear' => '25', 'playerSpawnRadiusFar' => '75'],
            collect($values['items'])->pluck('val', 'name')->all(),
        );
    }

    public function test_values_reads_agent_level_items_for_infected_territories(): void
    {
        $values = app(EnvironmentXmlEditor::class)->values($this->xml, 'ZombieTest');

        $this->assertTrue($values['is_infected']);
        $this->assertSame(
            ['countMin' => '0', 'countMax' => '0'],
            collect($values['agents'][0]['items'])->pluck('val', 'name')->all(),
        );
        $this->assertSame([['name' => 'herdsCount', 'val' => '0']], $values['items']);
    }

    public function test_update_replaces_items_and_behavior_without_touching_agents(): void
    {
        $editor = app(EnvironmentXmlEditor::class);
        $updated = $editor->update($this->xml, 'AmbientHen', [
            'behavior' => 'DZAmbientLifeGroupBehV2',
            'items' => [['name' => 'globalCountMax', 'val' => 100]],
        ]);

        $values = $editor->values($updated, 'AmbientHen');
        $this->assertSame('DZAmbientLifeGroupBehV2', $values['behavior']);
        $this->assertSame([['name' => 'globalCountMax', 'val' => '100']], $values['items']);
        $this->assertCount(2, $values['agents']);
    }

    public function test_add_territory_creates_entry_and_registers_file_reference(): void
    {
        $editor = app(EnvironmentXmlEditor::class);
        $updated = $editor->addTerritory($this->xml, [
            'name' => 'Lynx',
            'type' => 'Herd',
            'behavior' => 'DZWolfGroupBeh',
            'file' => 'lynx_territories.xml',
            'agents' => [
                ['type' => 'Male', 'chance' => 1, 'spawns' => [['configName' => 'Animal_LynxLynx', 'chance' => 1]]],
            ],
            'items' => [['name' => 'globalCountMax', 'val' => 10]],
        ]);

        $entries = $editor->entries($updated);
        $this->assertCount(8, $entries);
        $added = collect($entries)->firstWhere('name', 'Lynx');
        $this->assertSame('lynx_territories.xml', $added['file']);
        $this->assertSame(1, $added['agent_count']);
        $this->assertContains('lynx_territories.xml', $editor->fileReferences($updated));
    }

    public function test_add_territory_rejects_duplicate_name_case_insensitively(): void
    {
        $this->expectException(RuntimeException::class);
        app(EnvironmentXmlEditor::class)->addTerritory($this->xml, [
            'name' => 'wolf', 'type' => 'Herd', 'behavior' => 'DZWolfGroupBeh',
        ]);
    }

    public function test_remove_territory_deletes_only_the_targeted_entry(): void
    {
        $editor = app(EnvironmentXmlEditor::class);
        $updated = $editor->removeTerritory($this->xml, 'Cow');
        $entries = $editor->entries($updated);

        $this->assertCount(6, $entries);
        $this->assertNull(collect($entries)->firstWhere('name', 'Cow'));
        $this->assertNotNull(collect($entries)->firstWhere('name', 'Deer'));
    }

    public function test_territory_targets_maps_species_to_file_with_infected_flag(): void
    {
        $targets = collect(app(EnvironmentXmlEditor::class)->territoryTargets($this->xml))->keyBy('name');

        $this->assertSame('sheep_goat_territories.xml', $targets['Goat']['file']);
        $this->assertSame('sheep_goat_territories.xml', $targets['Sheep']['file']);
        $this->assertFalse($targets['Wolf']['is_infected']);
        $this->assertTrue($targets['ZombieTest']['is_infected']);
    }
}
