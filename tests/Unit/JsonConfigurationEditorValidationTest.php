<?php

namespace Tests\Unit;

use App\Services\Revision\JsonConfigurationEditor;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JsonConfigurationEditorValidationTest extends TestCase
{
    public function test_it_preserves_boolean_values_and_accepts_documented_ranges(): void
    {
        $content = file_get_contents(base_path('dokumenty/dayzOffline.chernarusplus/cfggameplay.json'));
        $updated = app(JsonConfigurationEditor::class)->update($content, [
            'GeneralData.disableBaseDamage' => true,
            'PlayerData.StaminaData.staminaMinCap' => '5',
            'PlayerData.WeaponObstructionData.staticMode' => '2',
        ]);

        $data = json_decode($updated, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($data['GeneralData']['disableBaseDamage']);
        $this->assertSame(5, $data['PlayerData']['StaminaData']['staminaMinCap']);
        $this->assertSame(2, $data['PlayerData']['WeaponObstructionData']['staticMode']);
    }

    public function test_it_rejects_invalid_documented_gameplay_range(): void
    {
        $this->expectException(ValidationException::class);
        $content = file_get_contents(base_path('dokumenty/dayzOffline.chernarusplus/cfggameplay.json'));

        app(JsonConfigurationEditor::class)->update($content, [
            'PlayerData.MovementData.timeToSprint' => 0,
        ]);
    }
}
