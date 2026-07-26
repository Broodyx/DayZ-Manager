<?php

namespace Tests\Unit;

use App\Services\Dayz\ConfigurationFieldMetadata;
use App\Services\Revision\JsonConfigurationEditor;
use App\Services\Revision\ServerConfigEditor;
use Tests\TestCase;

class ConfigurationFieldMetadataTest extends TestCase
{
    public function test_every_server_setting_in_reference_file_has_specific_metadata(): void
    {
        $content = file_get_contents(base_path('dokumenty/serverDZ.cfg'));
        $values = app(ServerConfigEditor::class)->parse($content);
        $metadata = app(ConfigurationFieldMetadata::class);

        foreach (array_keys($values) as $key) {
            $description = $metadata->server($key);
            $this->assertStringNotContainsString('tuto volbu neuvádí', $description, "Missing metadata for {$key}");
        }
    }

    public function test_boolean_server_setting_uses_boolean_not_numeric_wording(): void
    {
        $description = app(ConfigurationFieldMetadata::class)->server('storeHouseStateDisabled');

        $this->assertStringContainsString('false', $description);
        $this->assertStringContainsString('true', $description);
        $this->assertStringNotContainsString('0 =', $description);
        $this->assertStringNotContainsString('1 =', $description);
    }

    public function test_every_reference_gameplay_field_has_a_type_or_range_description(): void
    {
        $content = file_get_contents(base_path('dokumenty/dayzOffline.chernarusplus/cfggameplay.json'));
        $fields = app(JsonConfigurationEditor::class)->fields($content);
        $metadata = app(ConfigurationFieldMetadata::class);

        foreach ($fields as $field) {
            $description = $metadata->json($field);
            $this->assertNotSame('', trim($description), "Empty metadata for {$field['path']}");
            $this->assertMatchesRegularExpression(
                '/(true|false|0|1|čís|hodnot|pole|řetězc|sekund|gram|stup|minimum|maximum|rozsah|limit)/iu',
                $description,
                "Metadata does not state a type or bound for {$field['path']}",
            );
        }
    }
}
