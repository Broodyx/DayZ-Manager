<?php

namespace Tests\Feature;

use App\Filament\Pages\FtpExplorer;
use App\Models\Project;
use App\Models\User;
use App\Services\Ftp\FtpBrowser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FtpExplorerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_asks_for_ftp_details_when_none_are_configured(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'No FTP', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $this->actingAs($user);
        Livewire::test(FtpExplorer::class)
            ->set('projectId', $project->id)
            ->call('loadDirectory')
            ->assertSet('connected', false)
            ->assertSee('nemá nastavené FTP připojení');
    }

    public function test_it_lists_directory_entries_from_the_browser_service(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With FTP', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('listDirectory')->andReturn([
                ['name' => 'custom', 'path' => 'custom', 'type' => 'dir', 'size' => null, 'known' => false, 'category' => null],
                ['name' => 'types.xml', 'path' => 'types.xml', 'type' => 'file', 'size' => 2048, 'known' => true, 'category' => 'economy'],
            ]);
        });

        $this->actingAs($user);
        Livewire::test(FtpExplorer::class)
            ->set('projectId', $project->id)
            ->call('loadDirectory')
            ->assertSet('connected', true)
            ->assertSee('custom')
            ->assertSee('atypické')
            ->assertSee('types.xml')
            ->assertSee('známý');
    }

    public function test_up_strips_the_last_path_segment(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With FTP', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);
        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('listDirectory')->andReturn([]);
        });

        $this->actingAs($user);
        Livewire::test(FtpExplorer::class)
            ->set('projectId', $project->id)
            ->call('open', 'custom/nested')
            ->assertSet('currentPath', 'custom/nested')
            ->call('up')
            ->assertSet('currentPath', 'custom');
    }
}
