<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadAllRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_all_route_streams_a_zip_and_marks_files_downloaded(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Download all test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();

        $this->actingAs($user)
            ->get(route('project.configuration.download-all', ['project' => $project->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $this->assertNotNull($revision->fresh()->downloaded_at);
    }

    public function test_download_all_route_redirects_with_a_warning_for_an_empty_project(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Empty download all test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $this->actingAs($user)
            ->get(route('project.configuration.download-all', ['project' => $project->id]))
            ->assertRedirect(\App\Filament\Pages\ConfigurationWizard::getUrl(['project' => $project->id]))
            ->assertSessionHas('status_warning');
    }

    public function test_a_stranger_cannot_download_another_users_project_bundle(): void
    {
        Storage::fake('dayz');
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $owner->id, 'name' => 'Owned by someone else', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types></types>'),
            $owner,
        );

        $this->actingAs($stranger)
            ->get(route('project.configuration.download-all', ['project' => $project->id]))
            ->assertForbidden();
    }
}
