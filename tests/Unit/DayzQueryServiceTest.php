<?php

namespace Tests\Unit;

use App\Services\Dayz\DayzQueryService;
use Tests\TestCase;

class DayzQueryServiceTest extends TestCase
{
    public function test_it_parses_a2s_info_response(): void
    {
        $response = "\xff\xff\xff\xffI".chr(17).'Test Server'."\0chernarusplus\0dayz\0DayZ\0".pack('v', 221100).chr(4).chr(60);
        $result = app(DayzQueryService::class)->parseInfoResponse($response);
        $this->assertTrue($result['online']);
        $this->assertSame('Test Server', $result['name']);
        $this->assertSame('chernarusplus', $result['map']);
        $this->assertSame(4, $result['players']);
        $this->assertSame(60, $result['max_players']);
    }

    public function test_it_rejects_invalid_response(): void
    {
        $this->expectException(\RuntimeException::class);
        app(DayzQueryService::class)->parseInfoResponse('invalid');
    }
}
