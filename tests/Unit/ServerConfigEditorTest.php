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
}
