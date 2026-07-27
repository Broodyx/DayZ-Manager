<?php

namespace Tests\Feature;

use App\Filament\Pages\LogAnalyzer;
use App\Models\LogAnalysis;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LogAnalyzerHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyzing_a_log_saves_it_to_history(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Log history test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $log = "10:00:00 !!! [CE][PlayerSpawnPoints] :: There are no valid groups.\n10:00:01 !!! [CE][PlayerSpawnPoints] :: NO VALID SPAWNS, players will spawn at { 0,0,0 } !!!";

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->set('logContent', $log)
            ->call('analyze')
            ->assertSet('viewingHistoryId', null);

        $this->assertSame(1, LogAnalysis::query()->count());
        $item = LogAnalysis::query()->firstOrFail();
        $this->assertSame($project->id, $item->project_id);
        $this->assertSame($user->id, $item->created_by);
        $this->assertSame(2, $item->critical_count);
        $this->assertTrue(Storage::disk('dayz')->exists($item->storage_path));
        $this->assertSame($log, Storage::disk('dayz')->get($item->storage_path));
    }

    public function test_loading_from_history_restores_the_log_and_findings(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Log history test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $log = "10:00:00 !!! [CE][DE][SPAWNS] :: [WARNING] :: Skipping entry for non-existing event 'VehicleTransitBus'.";

        $component = Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->set('logContent', $log)
            ->call('analyze');

        $historyId = LogAnalysis::query()->firstOrFail()->id;

        $component->call('clear')
            ->assertSet('logContent', '')
            ->assertSet('analyzed', false)
            ->call('loadFromHistory', $historyId)
            ->assertSet('logContent', $log)
            ->assertSet('analyzed', true)
            ->assertSet('viewingHistoryId', $historyId)
            ->assertSee("VehicleTransitBus");
    }

    public function test_a_user_cannot_load_or_delete_another_users_history(): void
    {
        Storage::fake('dayz');
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $owner->id, 'name' => 'Log history test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        Livewire::actingAs($owner)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->set('logContent', "10:00:00 BattlEye Server: Initialized (v1.220, DayZ 1.29.163451)")
            ->call('analyze');
        $historyId = LogAnalysis::query()->firstOrFail()->id;

        Livewire::actingAs($stranger)->test(LogAnalyzer::class)
            ->call('loadFromHistory', $historyId)
            ->assertSet('viewingHistoryId', null)
            ->call('deleteHistory', $historyId);

        $this->assertSame(1, LogAnalysis::query()->count());
    }

    public function test_deleting_history_removes_the_stored_log_and_record(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Log history test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->set('logContent', "10:00:00 BattlEye Server: Initialized (v1.220, DayZ 1.29.163451)")
            ->call('analyze');
        $item = LogAnalysis::query()->firstOrFail();

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->call('deleteHistory', $item->id);

        $this->assertSame(0, LogAnalysis::query()->count());
        $this->assertFalse(Storage::disk('dayz')->exists($item->storage_path));
    }
}
