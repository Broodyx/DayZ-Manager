<?php

namespace Tests\Unit;

use App\Services\Revision\ServerConfigEditor;
use Tests\TestCase;

class ServerConfigEditorTest extends TestCase
{
    public function test_it_preserves_cfg_value_types_when_updating(): void
    {
        $editor = app(ServerConfigEditor::class);
        $updated = $editor->update("hostname = \"Old\";\nmaxPlayers = 10;\nenableCfgGameplayFile = 0;\n", [
            'hostname' => 'New server',
            'maxPlayers' => '30',
            'enableCfgGameplayFile' => '1',
        ]);

        $this->assertStringContainsString('hostname = "New server";', $updated);
        $this->assertStringContainsString('maxPlayers = 30;', $updated);
        $this->assertStringContainsString('enableCfgGameplayFile = 1;', $updated);
    }

    public function test_it_validates_official_time_and_port_ranges(): void
    {
        $this->expectException(\RuntimeException::class);
        app(ServerConfigEditor::class)->validate(['serverTimeAcceleration' => 100]);
    }

    public function test_it_distinguishes_boolean_and_numeric_switches(): void
    {
        $editor = app(ServerConfigEditor::class);
        $editor->validate([
            'storeHouseStateDisabled' => 'false',
            'enableWhitelist' => '1',
            'verifySignatures' => '2',
            'guaranteedUpdates' => '1',
        ]);

        $this->expectException(\RuntimeException::class);
        $editor->validate(['storeHouseStateDisabled' => '0']);
    }

    public function test_it_rejects_unsupported_signature_modes(): void
    {
        $this->expectException(\RuntimeException::class);
        app(ServerConfigEditor::class)->validate(['verifySignatures' => '1']);
    }

    public function test_it_validates_console_mouse_and_keyboard_flag_and_network_ranges(): void
    {
        $editor = app(ServerConfigEditor::class);
        $editor->validate(['enableMouseAndKeyboard' => '1', 'networkRangeClose' => '20', 'networkRangeFar' => '1000']);

        $this->expectException(\RuntimeException::class);
        $editor->validate(['enableMouseAndKeyboard' => '2']);
    }

    public function test_it_rejects_negative_network_range(): void
    {
        $this->expectException(\RuntimeException::class);
        app(ServerConfigEditor::class)->validate(['networkRangeFar' => -1]);
    }
}
