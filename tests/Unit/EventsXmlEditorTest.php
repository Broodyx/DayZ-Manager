<?php

namespace Tests\Unit;

use App\Services\Dayz\EventsXmlEditor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EventsXmlEditorTest extends TestCase
{
    private const SAMPLE = <<<'XML'
<events>
    <event name="StaticHeliCrash">
        <nominal>1</nominal>
        <min>0</min>
        <max>2</max>
        <lifetime>3600</lifetime>
        <restock>0</restock>
        <saferadius>100</saferadius>
        <distanceradius>100</distanceradius>
        <cleanupradius>100</cleanupradius>
        <flags deletable="0" init_random="1" remove_damaged="0"/>
        <position>fixed</position>
        <limit>mixed</limit>
        <active>1</active>
        <children>
            <child lootmax="0" lootmin="0" max="1" min="1" type="Wreck_UH1Y"/>
        </children>
    </event>
    <event name="InfectedArmy">
        <nominal>30</nominal>
        <min>15</min>
        <max>45</max>
        <lifetime>900</lifetime>
    </event>
</events>
XML;

    public function test_entries_returns_lightweight_summary_for_every_event(): void
    {
        $entries = (new EventsXmlEditor())->entries(self::SAMPLE);

        $this->assertCount(2, $entries);
        $this->assertSame(['name' => 'StaticHeliCrash', 'nominal' => 1, 'min' => 0, 'max' => 2, 'children_count' => 1], $entries[0]);
    }

    public function test_values_returns_full_field_set_for_one_event(): void
    {
        $values = (new EventsXmlEditor())->values(self::SAMPLE, 'StaticHeliCrash');

        $this->assertSame(1, $values['nominal']);
        $this->assertSame(100, $values['saferadius']);
        $this->assertFalse($values['deletable']);
        $this->assertTrue($values['init_random']);
        $this->assertSame('fixed', $values['position']);
        $this->assertTrue($values['active']);
        $this->assertSame([['type' => 'Wreck_UH1Y', 'min' => 1, 'max' => 1, 'lootmin' => 0, 'lootmax' => 0]], $values['children']);
    }

    public function test_update_changes_only_the_targeted_event(): void
    {
        $updated = (new EventsXmlEditor())->update(self::SAMPLE, 'StaticHeliCrash', [
            'nominal' => 5, 'min' => 1, 'max' => 3, 'lifetime' => 1800, 'restock' => 60,
            'saferadius' => 50, 'distanceradius' => 50, 'cleanupradius' => 50,
            'deletable' => true, 'init_random' => false, 'remove_damaged' => true,
            'position' => 'player', 'limit' => 'nearest', 'active' => false,
            'children' => [['type' => 'Wreck_UH1Y', 'min' => 2, 'max' => 4, 'lootmin' => 1, 'lootmax' => 2]],
        ]);

        $values = (new EventsXmlEditor())->values($updated, 'StaticHeliCrash');
        $this->assertSame(5, $values['nominal']);
        $this->assertTrue($values['deletable']);
        $this->assertFalse($values['init_random']);
        $this->assertSame('player', $values['position']);
        $this->assertFalse($values['active']);
        $this->assertSame(2, $values['children'][0]['min']);

        $untouched = (new EventsXmlEditor())->values($updated, 'InfectedArmy');
        $this->assertSame(30, $untouched['nominal']);
    }

    public function test_limit_child_is_accepted_and_not_downgraded_to_mixed(): void
    {
        $updated = (new EventsXmlEditor())->update(self::SAMPLE, 'StaticHeliCrash', ['limit' => 'child']);
        $this->assertSame('child', (new EventsXmlEditor())->values($updated, 'StaticHeliCrash')['limit']);

        $appended = (new EventsXmlEditor())->appendEvent(self::SAMPLE, 'StaticWeaponsChest', ['limit' => 'child', 'child_type' => 'Barrel_Green']);
        $values = (new EventsXmlEditor())->values($appended, 'StaticWeaponsChest');
        $this->assertSame('child', $values['limit']);
        $this->assertSame('Barrel_Green', $values['children'][0]['type']);
    }

    public function test_remove_deletes_the_event(): void
    {
        $updated = (new EventsXmlEditor())->remove(self::SAMPLE, 'InfectedArmy');
        $entries = (new EventsXmlEditor())->entries($updated);

        $this->assertCount(1, $entries);
        $this->assertSame('StaticHeliCrash', $entries[0]['name']);
    }

    public function test_update_finds_the_event_despite_a_stray_space_in_its_name_attribute(): void
    {
        // A hand-edited events.xml can end up with a trailing/leading space in name="..." that
        // renders identically to the trimmed name — this must not make a visibly-matching event
        // "not found" (reported symptom: container-contents sync failing with a false
        // "event not found" warning even though the event was clearly present).
        $padded = str_replace('name="StaticHeliCrash"', 'name="StaticHeliCrash "', self::SAMPLE);

        $updated = (new EventsXmlEditor())->update($padded, 'StaticHeliCrash', ['nominal' => 9]);

        $this->assertSame(9, (new EventsXmlEditor())->values($updated, 'StaticHeliCrash')['nominal']);
    }

    public function test_update_rejects_invalid_child_classname(): void
    {
        $this->expectException(RuntimeException::class);
        (new EventsXmlEditor())->update(self::SAMPLE, 'StaticHeliCrash', [
            'children' => [['type' => 'bad name!', 'min' => 1, 'max' => 1, 'lootmin' => 0, 'lootmax' => 0]],
        ]);
    }

    public function test_child_classname_renames_the_spawned_object_and_preserves_its_other_attributes(): void
    {
        $updated = (new EventsXmlEditor())->update(self::SAMPLE, 'StaticHeliCrash', [
            'child_classname' => 'Wreck_Mi8',
        ]);

        $values = (new EventsXmlEditor())->values($updated, 'StaticHeliCrash');
        $this->assertSame('Wreck_Mi8', $values['children'][0]['type']);
        // min/max/lootmin/lootmax on the existing <child> must survive untouched — only its
        // type attribute changes.
        $this->assertSame(1, $values['children'][0]['min']);
        $this->assertSame(1, $values['children'][0]['max']);
    }

    public function test_child_classname_rejects_invalid_names(): void
    {
        $this->expectException(RuntimeException::class);
        (new EventsXmlEditor())->update(self::SAMPLE, 'StaticHeliCrash', ['child_classname' => 'bad name!']);
    }

    public function test_supports_requires_events_root_and_matching_filename(): void
    {
        $editor = new EventsXmlEditor();
        $this->assertTrue($editor->supports('events.xml', self::SAMPLE));
        $this->assertFalse($editor->supports('cfgeventspawns.xml', self::SAMPLE));
        $this->assertFalse($editor->supports('events.xml', '<not-events/>'));
    }
}
