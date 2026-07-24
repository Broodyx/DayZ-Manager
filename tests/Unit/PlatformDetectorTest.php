<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Services\PlatformDetection\PlatformCompatibility;
use App\Services\PlatformDetection\PlatformDetector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlatformDetectorTest extends TestCase
{
    public function test_it_detects_steam_specific_markers(): void
    {
        $result = (new PlatformDetector)->detect('-mod=@CF;@Expansion');
        $this->assertSame('steam', $result->platform);
        $this->assertGreaterThanOrEqual(65, $result->confidence);
    }

    public function test_it_does_not_claim_playstation_from_generic_xml(): void
    {
        $result = (new PlatformDetector)->detect('<types/>');
        $this->assertSame('unknown', $result->platform);
        $this->assertLessThan(100, $result->confidence);
    }

    public function test_console_editor_rejects_pc_only_mod_configuration(): void
    {
        $project = new Project(['platform' => 'playstation']);

        $this->expectException(ValidationException::class);

        (new PlatformCompatibility(new PlatformDetector))->assertEditable(
            $project,
            'launch="-mod=@Community-Framework"',
        );
    }

    public function test_steam_editor_allows_pc_mod_configuration(): void
    {
        $project = new Project(['platform' => 'steam']);

        (new PlatformCompatibility(new PlatformDetector))->assertEditable(
            $project,
            'launch="-mod=@Community-Framework"',
        );

        $this->assertTrue(true);
    }
}
