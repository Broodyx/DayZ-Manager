<?php

namespace Tests\Unit;

use App\Services\Revision\XmlConfigurationEditor;
use Tests\TestCase;

class XmlConfigurationEditorTest extends TestCase
{
    public function test_it_groups_repeated_records_by_their_semantic_name(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<events>
    <event name="VehicleSedan02"><nominal>5</nominal><min>2</min></event>
    <event name="AnimalDeer"><nominal>8</nominal><min>3</min></event>
</events>
XML;

        $fields = app(XmlConfigurationEditor::class)->fields($xml);

        $this->assertSame(
            ['Event · VehicleSedan02', 'Event · AnimalDeer'],
            collect($fields)->pluck('group')->unique()->values()->all(),
        );
        $this->assertCount(6, $fields);
    }

    public function test_it_round_trips_attributes_text_and_boolean_values(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<root><zone name="Test"><active>true</active><value amount="4"/></zone></root>
XML;

        $editor = app(XmlConfigurationEditor::class);
        $updated = $editor->update($xml, [
            '/root[1]/zone[1]@name' => 'Updated',
            '/root[1]/zone[1]/active[1]/text()' => 'false',
            '/root[1]/zone[1]/value[1]@amount' => 7,
        ]);

        $this->assertStringContainsString('<zone name="Updated">', $updated);
        $this->assertStringContainsString('<active>false</active>', $updated);
        $this->assertStringContainsString('<value amount="7"/>', $updated);
    }
}
