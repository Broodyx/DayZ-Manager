<?php

namespace Tests\Feature;

use App\Filament\Pages\LogAnalyzer;
use App\Models\Project;
use App\Models\User;
use App\Services\Ftp\FtpBrowser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LogAnalyzerFtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_ftp_log_picker_is_hidden_without_a_configured_log_path(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'No log path', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);

        $this->actingAs($user)
            ->get('/admin/log-analyzer?project='.$project->id)
            ->assertOk()
            ->assertDontSee('Načíst seznam logů z FTP');
    }

    public function test_loading_ftp_log_files_lists_them(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With log path', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
            'ftp_log_path' => '0:/dayzps/config/',
        ]);

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('listLogFiles')->once()->andReturn([
                ['name' => 'DayZServer_PS4_x64_2026-08-01_08-37-57.RPT', 'path' => 'DayZServer_PS4_x64_2026-08-01_08-37-57.RPT', 'size' => 2048, 'modified' => strtotime('2026-08-01 08:37:57')],
            ]);
        });

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->call('loadFtpLogFiles')
            ->assertSet('ftpLogsLoaded', true)
            ->assertSet('ftpLogFiles.0.name', 'DayZServer_PS4_x64_2026-08-01_08-37-57.RPT')
            ->assertSee('DayZServer_PS4_x64_2026-08-01_08-37-57.RPT');
    }

    public function test_loading_one_ftp_log_file_fills_the_log_content(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With log path', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
            'ftp_log_path' => '0:/dayzps/config/',
        ]);

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('readLogFile')
                ->with(\Mockery::on(fn ($arg): bool => $arg instanceof Project), 'DayZServer_PS4_x64_2026-08-01_08-37-57.RPT')
                ->once()
                ->andReturn("8:38:15.447 !!! [CE][PlayerSpawnPoints] :: There are no valid groups.");
        });

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->call('loadFtpLogFile', 'DayZServer_PS4_x64_2026-08-01_08-37-57.RPT')
            ->assertSet('logContent', "8:38:15.447 !!! [CE][PlayerSpawnPoints] :: There are no valid groups.")
            ->assertSet('analyzed', false);
    }

    public function test_loading_an_ftp_log_file_reports_a_friendly_error(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With log path', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
            'ftp_log_path' => '0:/dayzps/config/',
        ]);

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('readLogFile')->once()->andThrow(new \RuntimeException('Připojení vypršelo.'));
        });

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->call('loadFtpLogFile', 'missing.RPT')
            ->assertSet('logContent', '');
    }
}
