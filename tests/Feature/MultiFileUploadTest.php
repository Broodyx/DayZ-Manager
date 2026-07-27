<?php

namespace Tests\Feature;

use App\Filament\Pages\ConfigurationWizard;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiFileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_several_files_at_once_imports_each_and_redirects_to_the_wizard(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Multi upload test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $response = $this->actingAs($user)->post(route('configuration-import.upload'), [
            'project_id' => $project->id,
            'platform' => 'playstation',
            'files' => [
                UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
                UploadedFile::fake()->createWithContent('events.xml', '<events></events>'),
            ],
        ]);

        $response->assertRedirect(ConfigurationWizard::getUrl(['project' => $project->id]));
        $response->assertSessionHas('status', fn ($value) => str_contains($value, 'types.xml') && str_contains($value, 'events.xml'));

        $this->assertSame(2, $project->revisions()->count());
        $this->assertSame('playstation', $project->fresh()->platform);
    }

    public function test_uploading_a_single_file_still_redirects_straight_to_its_editor(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Single upload test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $response = $this->actingAs($user)->post(route('configuration-import.upload'), [
            'project_id' => $project->id,
            'platform' => 'playstation',
            'files' => [
                UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            ],
        ]);

        $revision = $project->revisions()->firstOrFail();
        $response->assertRedirect('/admin/projects/'.$project->id.'/configuration?revision='.$revision->id);
        $response->assertSessionHas('status', 'Importováno: types.xml');
    }

    public function test_a_failed_file_in_a_batch_is_reported_without_blocking_the_rest(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Partial failure test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $response = $this->actingAs($user)->post(route('configuration-import.upload'), [
            'project_id' => $project->id,
            'platform' => 'playstation',
            'files' => [
                UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
                UploadedFile::fake()->create('not-a-config.exe', 10),
            ],
        ]);

        $response->assertSessionHas('status', fn ($value) => str_contains($value, 'types.xml'));
        $response->assertSessionHas('status_warning', fn ($value) => str_contains($value, 'not-a-config.exe'));
        $this->assertSame(1, $project->revisions()->count());
    }
}
