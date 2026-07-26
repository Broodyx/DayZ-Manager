<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use App\Services\Revision\ConfigurationRevisionEditor;
use Database\Seeders\DayzDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Filament\Resources\ProjectResource\Pages\EditConfiguration;
use Tests\TestCase;
use ZipArchive;

class ConfigurationImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_validates_and_creates_a_revision_for_an_xml_import(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Chernarus test',
            'platform' => 'unknown',
            'map' => 'ChernarusPlus',
        ]);
        $file = UploadedFile::fake()->createWithContent(
            'types.xml',
            '<?xml version="1.0"?><types><type name="AKM"/></types>',
        );

        $import = app(ConfigurationImporter::class)->import($project, $file, $user);

        $this->assertSame('valid', $import->validation_status);
        $this->assertSame('types.xml', $import->original_filename);
        $this->assertSame('unknown', $import->detected_platform);
        Storage::disk('dayz')->assertExists($import->storage_path);
        $this->assertDatabaseHas('configuration_revisions', [
            'configuration_import_id' => $import->id,
            'revision_number' => 1,
            'created_by' => $user->id,
        ]);
    }

    public function test_it_records_xml_validation_errors_without_crashing(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Invalid XML test',
            'platform' => 'playstation',
            'map' => 'ChernarusPlus',
        ]);
        $file = UploadedFile::fake()->createWithContent('types.xml', '<types><type></types>');

        $import = app(ConfigurationImporter::class)->import($project, $file, $user);

        $this->assertSame('invalid', $import->validation_status);
        $this->assertNotEmpty($import->validation_errors);
        $this->assertSame('types.xml', $import->validation_errors[0]['file']);
    }

    public function test_zip_bundle_creates_individually_editable_imports_and_revisions(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('Lokální PHP nemá ext-zip; produkční Docker image ji instaluje.');
        }
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Bundle test',
            'platform' => 'unknown',
            'map' => 'ChernarusPlus',
        ]);
        $temporary = tempnam(sys_get_temp_dir(), 'dayz-');
        @unlink($temporary);
        $path = $temporary.'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('db/types.xml', '<types><type name="AKM"/></types>');
        $zip->addFromString('cfggameplay.json', '{"version":123,"PlayerData":{"spawnGearPresetFiles":[]}}');
        $zip->close();

        try {
            app(ConfigurationImporter::class)->import(
                $project,
                new UploadedFile($path, 'mission.zip', 'application/zip', null, true),
                $user,
            );
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('configuration_imports', ['original_filename' => 'db/types.xml']);
        $this->assertDatabaseHas('configuration_imports', ['original_filename' => 'cfggameplay.json']);
        $this->assertSame(2, $project->revisions()->count());
        $this->assertSame(['json', 'xml'], $project->revisions()->get()->map(fn ($revision) => pathinfo($revision->storage_path, PATHINFO_EXTENSION))->sort()->values()->all());
    }

    public function test_demo_data_can_be_created_repeatedly_for_the_signed_in_user(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $seeder = app(DayzDemoSeeder::class);

        $seeder->seedFor($user);
        $seeder->seedFor($user);

        $this->assertDatabaseCount('projects', 3);
        $this->assertDatabaseCount('configuration_imports', 13);
        $this->assertDatabaseCount('configuration_revisions', 13);
        $this->assertSame(3, $user->projects()->count());
    }

    public function test_import_and_demo_actions_are_visible_in_the_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/configuration-imports')
            ->assertOk()
            ->assertSee('Nahrát konfiguraci');

        $this->actingAs($user)
            ->get('/admin/projects')
            ->assertOk()
            ->assertSee('Vytvořit demo data');
    }

    public function test_dashboard_summarizes_projects_and_links_projects_to_the_editor(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        app(DayzDemoSeeder::class)->seedFor($user);
        $project = Project::query()
            ->where('user_id', $user->id)
            ->where('name', 'Chernarus Survival')
            ->firstOrFail();

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Položky v ekonomice serverů')
            ->assertSee('Naposledy upravené servery');

        $this->actingAs($user)
            ->get('/admin/projects')
            ->assertOk()
            ->assertSee("/admin/projects/{$project->id}/configuration", false)
            ->assertDontSee('Importovat konfiguraci');

        $this->actingAs($user)
            ->get('/admin/configuration-revisions')
            ->assertOk()
            ->assertSee('Server')
            ->assertSee('PlayStation');
    }

    public function test_types_xml_project_has_visual_and_raw_editor(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        app(DayzDemoSeeder::class)->seedFor($user);
        $project = Project::query()->where('user_id', $user->id)->where('name', 'Chernarus Survival')->firstOrFail();
        $typesRevision = $project->revisions()->whereHas('configurationImport', fn ($query) => $query->where('original_filename', 'types.xml'))->firstOrFail();

        $this->actingAs($user)
            ->get("/admin/projects/{$project->id}/configuration?revision={$typesRevision->id}")
            ->assertOk()
            ->assertSee('Vizuální editor')
            ->assertSee('Raw data')
            ->assertSee('Nahrát novou konfiguraci')
            ->assertSee('PlayStation')
            ->assertSee('Položky types.xml')
            ->assertSee('Nová položka')
            ->assertDontSee('No PC-only elements detected');
    }

    public function test_messages_xml_keeps_visual_editor_separate_from_raw_data_and_raw_can_be_copied(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Messages test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><messages><message><repeat>5</repeat><delay>10</delay><text>Unikátní testovací zpráva</text></message></messages>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('messages.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('visualKind', 'messages')
            ->assertSet('mode', 'visual')
            ->assertSee('messages.xml · vizuální editor')
            ->assertSet('messagesEntries.0.text', 'Unikátní testovací zpráva')
            ->set('mode', 'raw')
            ->assertSee('Raw data · messages.xml')
            ->assertSee('Kopírovat do schránky')
            ->assertSet('rawContent', $xml);
    }

    public function test_editor_save_creates_a_new_revision_without_overwriting_the_source(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        app(DayzDemoSeeder::class)->seedFor($user);
        $project = Project::query()->where('name', 'Chernarus Survival')->firstOrFail();
        $source = $project->revisions()
            ->with('configurationImport')
            ->whereHas('configurationImport', fn ($query) => $query->where('original_filename', 'types.xml'))
            ->firstOrFail();
        $original = Storage::disk('dayz')->get($source->storage_path);
        $updated = str_replace('<nominal>8</nominal>', '<nominal>18</nominal>', $original);

        $revision = app(ConfigurationRevisionEditor::class)->save(
            $project,
            $source,
            $updated,
            'Zvýšení AKM',
            $user,
        );

        $this->assertSame(12, $revision->revision_number);
        $this->assertSame($original, Storage::disk('dayz')->get($source->storage_path));
        $this->assertStringContainsString(
            '<nominal>18</nominal>',
            Storage::disk('dayz')->get($revision->storage_path),
        );
        $this->assertNotSame($source->sha256, $revision->sha256);
    }
}
