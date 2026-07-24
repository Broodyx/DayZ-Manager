<?php

namespace Tests\Unit;

use App\Services\Revision\WeatherXmlEditor;
use Tests\TestCase;

class WeatherXmlEditorTest extends TestCase
{
    private string $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<weather reset="0" enable="1">
    <overcast>
        <current actual="0.45" time="120" duration="240"/>
        <limits min="0.0" max="1.0"/>
        <timelimits min="900" max="1800"/>
        <changelimits min="0.0" max="1.0"/>
    </overcast>
    <rain>
        <current actual="0.0" time="120" duration="240"/>
        <limits min="0.0" max="1.0"/>
        <timelimits min="300" max="600"/>
        <changelimits min="0.0" max="1.0"/>
        <thresholds min="0.5" max="1.0" end="120"/>
    </rain>
    <storm density="1.0" threshold="0.7" timeout="25"/>
</weather>
XML;

    public function test_it_reads_all_weather_attribute_groups(): void
    {
        $values = app(WeatherXmlEditor::class)->values($this->xml);

        $this->assertTrue($values['enable']);
        $this->assertFalse($values['reset']);
        $this->assertSame(0.45, $values['overcast_current_actual']);
        $this->assertSame(0.5, $values['rain_thresholds_min']);
        $this->assertSame(0.42, $values['storm_timeout']);
    }

    public function test_it_updates_weather_and_preserves_other_sections(): void
    {
        $updated = app(WeatherXmlEditor::class)->update($this->xml, [
            'enable' => true,
            'reset' => true,
            'overcast_current_actual' => 0.8,
            'rain_thresholds_min' => 0.7,
            'storm_density' => 0.5,
        ]);

        $this->assertStringContainsString('<weather reset="1" enable="1">', $updated);
        $this->assertStringContainsString('actual="0.8"', $updated);
        $this->assertStringContainsString('thresholds min="0.7"', $updated);
        $this->assertStringContainsString('density="0.5"', $updated);
        $this->assertStringContainsString('<rain>', $updated);
    }
}
