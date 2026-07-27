<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\EditConfiguration;
use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UndeployedFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_newly_imported_file_is_flagged_as_undeployed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Deploy test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );

        $project->refresh()->load('revisions.configurationImport');
        $this->assertSame(['types.xml'], $project->undeployedFiles());
        $this->assertSame(1, $project->undeployed_files_count);
    }

    public function test_downloading_a_revision_clears_the_undeployed_flag(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Deploy test', 'platform' => 'playstation', 'map' => 'chernarusplus',
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

        $this->assertNotNull($revision->fresh()->downloaded_at);
        $project->refresh()->load('revisions.configurationImport');
        $this->assertSame([], $project->undeployedFiles());
    }

    public function test_editing_a_downloaded_file_flags_it_as_undeployed_again(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Deploy test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"><nominal>8</nominal></type></types>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();
        $this->actingAs($user)
            ->get(route('configuration-revision.download', ['project' => $project->id, 'revision' => $revision->id]))
            ->assertOk();

        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->call('selectType', 'AKM')
            ->set('typeForm.nominal', 12)
            ->call('saveType')
            ->assertHasNoErrors();

        $project->refresh()->load('revisions.configurationImport');
        $this->assertSame(['types.xml'], $project->undeployedFiles());
    }

    public function test_edit_configuration_reports_undeployed_revision_ids(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Deploy test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();

        $this->actingAs($user);
        $ids = Livewire::test(EditConfiguration::class, ['record' => $project->id])->instance()->undeployedRevisionIds();
        $this->assertSame([$revision->id], $ids);

        $this->get(route('configuration-revision.download', ['project' => $project->id, 'revision' => $revision->id]))->assertOk();

        $ids = Livewire::test(EditConfiguration::class, ['record' => $project->id])->instance()->undeployedRevisionIds();
        $this->assertSame([], $ids);
    }
}
