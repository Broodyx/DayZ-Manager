<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UndeployedFilesModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_clicking_the_undeployed_badge_opens_a_modal_with_download_links_instead_of_navigating(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Modal test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();

        $this->actingAs($user);
        Livewire::test(ListProjects::class)
            ->mountTableAction('showUndeployedFiles', $project)
            ->assertSee('types.xml')
            ->assertSee('revize #1')
            ->assertSee(route('configuration-revision.download', ['project' => $project->id, 'revision' => $revision->id]), false);
    }

    public function test_modal_shows_a_friendly_message_when_everything_is_deployed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Modal test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();
        $this->actingAs($user)
            ->get(route('configuration-revision.download', ['project' => $project->id, 'revision' => $revision->id]))
            ->assertOk();

        Livewire::test(ListProjects::class)
            ->mountTableAction('showUndeployedFiles', $project->fresh())
            ->assertSee('Všechny soubory jsou nasazené');
    }
}
