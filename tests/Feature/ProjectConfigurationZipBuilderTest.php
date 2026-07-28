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

    public function test_ps_bundle_keeps_files_flat_with_db_and_env_subfolders(): void
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
            UploadedFile::fake()->createWithContent('cfgeventspawns.xml', '<eventposdef></eventposdef>'),
            $user,
        );
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('wolf_territories.xml', '<territory-type></territory-type>'),
            $user,
        );
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('ban.txt', 'AAA'),
            $user,
        );
        $typesRevision = $project->revisions()->whereHas('configurationImport', fn ($q) => $q->where('original_filename', 'types.xml'))->firstOrFail();

        $result = app(ProjectConfigurationZipBuilder::class)->build($project->fresh());

        $this->assertEqualsCanonicalizing(
            ['db/types.xml', 'cfgeventspawns.xml', 'env/wolf_territories.xml', 'ban.txt'],
            $result['filenames'],
        );

        $zip = new ZipArchive;
        $zip->open($result['path']);
        $this->assertSame('<types><type name="AKM"/></types>', $zip->getFromName('db/types.xml'));
        $zip->close();
        unlink($result['path']);

        $this->assertNotNull($typesRevision->fresh()->downloaded_at);
    }

    public function test_pc_bundle_nests_mission_files_under_the_official_mission_folder(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'PC zip test', 'platform' => 'steam', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types></types>'),
            $user,
        );
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('cfgeventspawns.xml', '<eventposdef></eventposdef>'),
            $user,
        );
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('serverDZ.cfg', 'hostname = "X";'),
            $user,
        );

        $result = app(ProjectConfigurationZipBuilder::class)->build($project->fresh());

        $this->assertEqualsCanonicalizing([
            'dayzOffline.chernarusplus/db/types.xml',
            'dayzOffline.chernarusplus/cfgeventspawns.xml',
            'serverDZ.cfg',
        ], $result['filenames']);
    }

    public function test_pc_bundle_stays_flat_for_a_community_map_with_no_known_mission_folder(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'PC community map zip test', 'platform' => 'steam', 'map' => 'namalsk',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types></types>'),
            $user,
        );

        $result = app(ProjectConfigurationZipBuilder::class)->build($project->fresh());

        $this->assertSame(['db/types.xml'], $result['filenames']);
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
