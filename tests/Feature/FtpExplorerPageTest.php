<?php

namespace Tests\Feature;

use App\Filament\Pages\FtpExplorer;
use App\Models\ConfigurationImport;
use App\Models\Project;
use App\Models\User;
use App\Services\Ftp\FtpBrowser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
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

    public function test_import_all_in_folder_imports_every_file_entry_and_skips_folders(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With FTP', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);

        $this->mock(FtpBrowser::class, function ($mock) use ($project) {
            $mock->shouldReceive('listDirectory')->andReturn([
                ['name' => 'custom', 'path' => 'custom', 'type' => 'dir', 'size' => null, 'known' => false, 'category' => null],
                ['name' => 'types.xml', 'path' => 'types.xml', 'type' => 'file', 'size' => 100, 'known' => true, 'category' => 'economy'],
                ['name' => 'events.xml', 'path' => 'events.xml', 'type' => 'file', 'size' => 200, 'known' => true, 'category' => 'events'],
            ]);
            $mock->shouldReceive('importFile')
                ->with(Mockery::on(fn ($arg): bool => $arg instanceof Project && $arg->id === $project->id), 'types.xml', Mockery::any(), Mockery::any())
                ->once()
                ->andReturn(new ConfigurationImport(['original_filename' => 'types.xml']));
            $mock->shouldReceive('importFile')
                ->with(Mockery::on(fn ($arg): bool => $arg instanceof Project && $arg->id === $project->id), 'events.xml', Mockery::any(), Mockery::any())
                ->once()
                ->andReturn(new ConfigurationImport(['original_filename' => 'events.xml']));
        });

        $this->actingAs($user);
        Livewire::test(FtpExplorer::class)
            ->set('projectId', $project->id)
            ->call('loadDirectory')
            ->call('importAllInFolder')
            ->assertNotified('2× importováno')
            ->assertSet('lastImportSummary.imported', ['types.xml', 'events.xml'])
            ->assertSet('lastImportSummary.failed', [])
            ->assertSee('Importováno (2)');
    }

    public function test_import_all_in_folder_reports_partial_failures_in_the_persistent_summary(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With FTP', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);

        $this->mock(FtpBrowser::class, function ($mock) use ($project) {
            $mock->shouldReceive('listDirectory')->andReturn([
                ['name' => 'types.xml', 'path' => 'types.xml', 'type' => 'file', 'size' => 100, 'known' => true, 'category' => 'economy'],
                ['name' => 'broken.xml', 'path' => 'broken.xml', 'type' => 'file', 'size' => 100, 'known' => false, 'category' => null],
            ]);
            $mock->shouldReceive('importFile')
                ->with(Mockery::on(fn ($arg): bool => $arg instanceof Project && $arg->id === $project->id), 'types.xml', Mockery::any(), Mockery::any())
                ->once()
                ->andReturn(new ConfigurationImport(['original_filename' => 'types.xml']));
            $mock->shouldReceive('importFile')
                ->with(Mockery::on(fn ($arg): bool => $arg instanceof Project && $arg->id === $project->id), 'broken.xml', Mockery::any(), Mockery::any())
                ->once()
                ->andThrow(new \RuntimeException('Soubor se nepodařilo načíst.'));
        });

        $this->actingAs($user);
        Livewire::test(FtpExplorer::class)
            ->set('projectId', $project->id)
            ->call('loadDirectory')
            ->call('importAllInFolder')
            ->assertSet('lastImportSummary.imported', ['types.xml'])
            ->assertSet('lastImportSummary.failed', ['broken.xml: Soubor se nepodařilo načíst.'])
            ->assertSee('Importováno (1)')
            ->assertSee('Selhalo (1)');
    }

    public function test_listing_shows_when_a_file_was_last_imported(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'With FTP', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);
        ConfigurationImport::query()->create([
            'project_id' => $project->id,
            'original_filename' => 'types.xml',
            'storage_path' => "{$project->id}/imports/existing.xml",
            'sha256' => hash('sha256', 'x'),
            'detected_platform' => 'playstation',
            'detection_confidence' => 65,
            'validation_status' => 'valid',
            'imported_at' => now()->subDay(),
        ]);

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('listDirectory')->andReturn([
                ['name' => 'types.xml', 'path' => 'types.xml', 'type' => 'file', 'size' => 100, 'known' => true, 'category' => 'economy'],
                ['name' => 'events.xml', 'path' => 'events.xml', 'type' => 'file', 'size' => 100, 'known' => true, 'category' => 'events'],
            ]);
        });

        $this->actingAs($user);
        Livewire::test(FtpExplorer::class)
            ->set('projectId', $project->id)
            ->call('loadDirectory')
            ->assertSee('importováno '.now()->subDay()->format('d.m.Y'))
            ->assertSee('ještě neimportováno');
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
