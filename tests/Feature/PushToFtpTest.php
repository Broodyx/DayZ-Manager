<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\EditConfiguration;
use App\Models\Project;
use App\Models\User;
use App\Services\Ftp\FtpBrowser;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PushToFtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_pushing_to_ftp_writes_the_current_revision_to_the_correct_remote_path_and_marks_it_deployed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Push test', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('mapgroupproto.xml', '<prototype></prototype>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();
        $this->assertNull($revision->downloaded_at);

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('write')
                ->withArgs(fn (Project $p, string $path, string $content): bool => $path === 'mapgroupproto.xml' && $content === '<prototype></prototype>')
                ->once();
        });

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->call('pushToFtp')
            ->assertHasNoErrors();

        $this->assertNotNull($revision->fresh()->downloaded_at);
    }

    public function test_pushing_to_ftp_nests_the_path_for_a_pc_server(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Push test PC', 'platform' => 'steam', 'map' => 'chernarusplus',
            'ftp_protocol' => 'sftp', 'ftp_host' => 'example.com', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types></types>'),
            $user,
        );

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('write')
                ->withArgs(fn (Project $p, string $path, string $content): bool => $path === 'dayzOffline.chernarusplus/db/types.xml')
                ->once();
        });

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->call('pushToFtp')
            ->assertHasNoErrors();
    }

    public function test_a_failed_write_shows_an_error_and_does_not_mark_the_revision_deployed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Push failure test', 'platform' => 'playstation', 'map' => 'chernarusplus',
            'ftp_protocol' => 'ftp', 'ftp_host' => 'ms2321.gamedata.io', 'ftp_username' => 'user', 'ftp_password' => 'secret',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('mapgroupproto.xml', '<prototype></prototype>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();

        $this->mock(FtpBrowser::class, function ($mock) {
            $mock->shouldReceive('write')->once()->andThrow(new \RuntimeException('Spojení vypršelo.'));
        });

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->call('pushToFtp');

        $this->assertNull($revision->fresh()->downloaded_at);
    }
}
