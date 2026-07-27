<?php

namespace Tests\Unit;

use App\Services\Revision\JsonConfigurationEditor;
use Tests\TestCase;

class JsonConfigurationEditorTest extends TestCase
{
    public function test_it_supports_effect_areas_and_edits_json_arrays(): void
    {
        $editor = app(JsonConfigurationEditor::class);
        $content = '{"Areas":[{"AreaName":"NWAF","Data":{"Pos":[4036,0,11712],"Radius":150}}]}';

        $this->assertTrue($editor->supports($content));
        $fields = collect($editor->fields($content));
        $this->assertTrue($fields->contains(fn (array $field) => $field['path'] === 'Areas.0.AreaName' && $field['value'] === 'NWAF'));
        $this->assertTrue($fields->contains(fn (array $field) => $field['path'] === 'Areas.0.Data.Radius' && $field['value'] === 150));
        $this->assertSame(['Areas · NWAF'], $fields->pluck('group')->unique()->values()->all());

        $updated = $editor->update($content, [
            'Areas.0.AreaName' => 'Cherno',
            'Areas.0.Data.Radius' => 75,
        ]);
        $this->assertSame('Cherno', json_decode($updated, true, flags: JSON_THROW_ON_ERROR)['Areas'][0]['AreaName']);
    }

    public function test_it_exposes_gameplay_file_lists(): void
    {
        $editor = app(JsonConfigurationEditor::class);
        $content = '{"version":123,"PlayerData":{"spawnGearPresetFiles":["survivalist.json"]},"WorldData":{"objectSpawnersArr":["base.json"]}}';
        $paths = collect($editor->fields($content))->pluck('path');

        $this->assertContains('PlayerData.spawnGearPresetFiles', $paths);
        $this->assertContains('WorldData.objectSpawnersArr', $paths);
    }
}
