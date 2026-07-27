<?php

namespace Tests\Unit;

use App\Services\Dayz\ServerLogAnalyzer;
use PHPUnit\Framework\TestCase;

class ServerLogAnalyzerTest extends TestCase
{
    public function test_it_detects_no_valid_spawns_and_missing_event(): void
    {
        $log = <<<'LOG'
10:42:36 BattlEye Server: Initialized (v1.220, DayZ 1.29.163451)
10:43:12 !!! [CE][PlayerSpawnPoints] :: There are no valid groups.
10:43:12 !!! [CE][PlayerSpawnPoints] :: NO VALID SPAWNS, players will spawn at { 0,0,0 } !!!
10:43:26 !!! [CE][offlineDB] :: Type 'Static_FrozenScientist_DE' will be ignored. (Type does not exist. (Typo?))
10:43:26 !!! [CE][DE][SPAWNS] :: [WARNING] :: Skipping entry for non-existing event 'VehicleTransitBus'.
LOG;
        $result = (new ServerLogAnalyzer())->analyze($log);

        $bySeverity = collect($result['findings'])->groupBy('severity');
        $this->assertCount(3, $bySeverity->get('critical'));
        $this->assertCount(1, $bySeverity->get('warning'));
        $this->assertCount(1, $bySeverity->get('info'));

        $missingEvent = collect($result['findings'])->firstWhere('title', "cfgeventspawns.xml odkazuje na neexistující event 'VehicleTransitBus'");
        $this->assertNotNull($missingEvent);
        $this->assertStringContainsString('VehicleTransitBus', $missingEvent['action']);
        $this->assertSame('map-editor', $missingEvent['link']);
    }

    public function test_it_detects_restart_loop_stop(): void
    {
        $log = "Sun, 26 Jul 2026 13:28:15 +0200 ni13324876_1 restarted more than 10 times in a row. There seems to be something very wrong. Stopping server. (WINDOWS)";
        $result = (new ServerLogAnalyzer())->analyze($log);

        $this->assertCount(1, $result['findings']);
        $this->assertSame('critical', $result['findings'][0]['severity']);
    }

    public function test_it_groups_repeated_findings_and_counts_them(): void
    {
        $log = <<<'LOG'
10:00:00 !!! [CE][DE][SPAWNS] :: [WARNING] :: Skipping entry for non-existing event 'VehicleTransitBus'.
10:00:01 !!! [CE][DE][SPAWNS] :: [WARNING] :: Skipping entry for non-existing event 'VehicleTransitBus'.
10:00:02 !!! [CE][DE][SPAWNS] :: [WARNING] :: Skipping entry for non-existing event 'AnotherEvent'.
LOG;
        $result = (new ServerLogAnalyzer())->analyze($log);

        $this->assertCount(2, $result['findings']);
        $counts = collect($result['findings'])->pluck('count', 'title');
        $this->assertSame(2, $counts["cfgeventspawns.xml odkazuje na neexistující event 'VehicleTransitBus'"]);
        $this->assertSame(1, $counts["cfgeventspawns.xml odkazuje na neexistující event 'AnotherEvent'"]);
    }

    public function test_it_returns_no_findings_for_clean_log(): void
    {
        $result = (new ServerLogAnalyzer())->analyze("10:42:36 BattlEye Server: Initialized (v1.220, DayZ 1.29.163451)");
        $this->assertCount(1, $result['findings']);
        $this->assertSame('info', $result['findings'][0]['severity']);
    }
}
