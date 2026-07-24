<?php

namespace Tests\Unit;

use App\Services\PlatformDetection\PlatformDetector;
use PHPUnit\Framework\TestCase;

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
}
