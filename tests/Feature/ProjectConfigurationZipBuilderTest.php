<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use App\Services\Storage\ProjectConfigurationZipBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class ProjectConfigurationZipBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_bundles_the_latest_revision_of_every_file_and_marks_them_downloaded(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Zip test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/></types>'),
            $user,
        );
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('events.xml', '<events></events>'),
            $user,
        );
        $typesRevision = $project->revisions()->whereHas('configurationImport', fn ($q) => $q->where('original_filename', 'types.xml'))->firstOrFail();

        $result = app(ProjectConfigurationZipBuilder::class)->build($project->fresh());

        $this->assertFileExists($result['path']);
        $this->assertEqualsCanonicalizing(['types.xml', 'events.xml'], $result['filenames']);

        $zip = new ZipArchive;
        $zip->open($result['path']);
        $this->assertSame('<types><type name="AKM"/></types>', $zip->getFromName('types.xml'));
        $zip->close();
        unlink($result['path']);

        $this->assertNotNull($typesRevision->fresh()->downloaded_at);
    }

    public function test_it_throws_for_a_project_with_no_files(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Empty zip test', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $this->expectException(RuntimeException::class);
        app(ProjectConfigurationZipBuilder::class)->build($project);
    }
}
