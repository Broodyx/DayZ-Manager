<?php

namespace Tests\Feature;

use App\Filament\Resources\ConfigurationImportResource\Pages\ListConfigurationImports;
use App\Filament\Resources\ConfigurationRevisionResource\Pages\ListConfigurationRevisions;
use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DownloadTracksDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_downloading_from_the_revision_history_page_marks_it_deployed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Download test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();
        $this->assertNull($revision->downloaded_at);

        $this->actingAs($user);
        Livewire::test(ListConfigurationRevisions::class)
            ->callTableAction('download', $revision);

        $this->assertNotNull($revision->fresh()->downloaded_at);
    }

    public function test_downloading_from_the_import_history_page_marks_the_first_revision_deployed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Download test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        $import = app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        $revision = $import->revisions()->firstOrFail();
        $this->assertNull($revision->downloaded_at);

        $this->actingAs($user);
        Livewire::test(ListConfigurationImports::class)
            ->callTableAction('download', $import);

        $this->assertNotNull($revision->fresh()->downloaded_at);
    }
}
