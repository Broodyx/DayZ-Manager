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

    public function test_import_generated_file_creates_import_and_first_revision_without_an_upload(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Generated file test',
            'platform' => 'unknown',
            'map' => 'ChernarusPlus',
        ]);
        $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<territory-type>\n</territory-type>\n";

        $import = app(ConfigurationImporter::class)->importGeneratedFile($project, 'lynx_territories.xml', $content, $user);

        $this->assertSame('lynx_territories.xml', $import->original_filename);
        $this->assertSame('valid', $import->validation_status);
        Storage::disk('dayz')->assertExists($import->storage_path);
        $this->assertSame($content, Storage::disk('dayz')->get($import->storage_path));
        $this->assertDatabaseHas('configuration_revisions', [
            'configuration_import_id' => $import->id,
            'revision_number' => 1,
            'created_by' => $user->id,
        ]);
    }

    public function test_import_generated_file_rejects_invalid_xml(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Generated file invalid test',
            'platform' => 'unknown',
            'map' => 'ChernarusPlus',
        ]);

        $this->expectException(\RuntimeException::class);
        app(ConfigurationImporter::class)->importGeneratedFile($project, 'broken.xml', '<not-closed>', $user);
    }

    public function test_project_owner_can_read_raw_revision_but_another_user_cannot(): void
    {
        Storage::fake('dayz');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $owner->id,
            'name' => 'Raw endpoint test',
            'platform' => 'playstation',
            'map' => 'ChernarusPlus',
        ]);
        $content = '<?xml version="1.0"?><events><event name="VehicleSedan02"/></events>';
        $import = app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('events.xml', $content),
            $owner,
        );
        $revision = $import->revisions()->latest('id')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('configuration-revision.raw', [$project, $revision]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertContent($content);

        $this->actingAs($otherUser)
            ->get(route('configuration-revision.raw', [$project, $revision]))
            ->assertForbidden();
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
            ->assertSee('Přidat konfigurační soubor');

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

    public function test_configuration_wizard_opens_existing_file_in_editor_and_only_uploads_missing_areas(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Wizard server',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('serverDZ.cfg', 'hostname = "Wizard";'),
            $user,
        );
        $revision = $project->revisions()->firstOrFail();

        $this->actingAs($user)
            ->get('/admin/configuration-wizard?project='.$project->id)
            ->assertOk()
            ->assertSee('Wizard server')
            ->assertSee('/admin/projects/'.$project->id.'/configuration?revision='.$revision->id, false)
            ->assertSee('/admin/configuration-import?area=gameplay&amp;project='.$project->id, false)
            ->assertSee('Otevřít editor')
            ->assertSee('Nahrát soubor')
            ->assertSee('Hlavní pravidla serveru')
            ->assertSee('Gameplay serveru');
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

    public function test_messages_xml_infers_the_message_type_from_onconnect_and_shutdown_flags(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Messages type test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><messages>'
            .'<message><repeat>5</repeat><text>Broadcast</text></message>'
            .'<message><onconnect>1</onconnect><text>Welcome</text></message>'
            .'<message><shutdown>1</shutdown><deadline>30</deadline><repeat>5</repeat><text>Shutting down in #tmin</text></message>'
            .'</messages>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('messages.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('messagesEntries.0.type', 'broadcast')
            ->assertSet('messagesEntries.1.type', 'onconnect')
            ->assertSet('messagesEntries.2.type', 'shutdown');
    }

    public function test_changing_message_type_syncs_onconnect_and_shutdown_flags_and_clears_irrelevant_fields(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Messages type test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><messages><message><repeat>5</repeat><delay>10</delay><text>Broadcast</text></message></messages>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('messages.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('messagesEntries.0.type', 'broadcast')
            ->set('messagesEntries.0.type', 'onconnect')
            ->assertSet('messagesEntries.0.onconnect', '1')
            ->assertSet('messagesEntries.0.shutdown', '0')
            ->assertSet('messagesEntries.0.repeat', '')
            ->assertSet('messagesEntries.0.delay', '')
            ->set('messagesEntries.0.type', 'shutdown')
            ->assertSet('messagesEntries.0.onconnect', '0')
            ->assertSet('messagesEntries.0.shutdown', '1');
    }

    public function test_editor_redirects_to_the_wizard_for_a_project_with_no_files_instead_of_404ing(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Empty server', 'platform' => 'unknown', 'map' => 'chernarusplus',
        ]);

        $this->actingAs($user)
            ->get("/admin/projects/{$project->id}/configuration")
            ->assertRedirect(\App\Filament\Pages\ConfigurationWizard::getUrl(['project' => $project->id]));
    }

    public function test_whitelist_txt_editor_splits_id_and_comment_and_round_trips_them(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Whitelist test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('whitelist.txt', "1111111111112222222222222333333333XXXXXXAAAA\t//Example of a character ID\n"),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('whitelistEntries.0.id', '1111111111112222222222222333333333XXXXXXAAAA')
            ->assertSet('whitelistEntries.0.comment', 'Example of a character ID')
            ->set('newWhitelistId', '9999999999999999999999999999999999999999999AA')
            ->set('newWhitelistComment', 'Nový hráč')
            ->call('addWhitelistEntry')
            ->call('saveWhitelist')
            ->assertHasNoErrors();

        $saved = Storage::disk('dayz')->get($project->revisions()->latest('revision_number')->firstOrFail()->storage_path);
        $this->assertStringContainsString("1111111111112222222222222333333333XXXXXXAAAA\t//Example of a character ID", $saved);
        $this->assertStringContainsString("9999999999999999999999999999999999999999999AA\t//Nový hráč", $saved);
    }

    public function test_ban_txt_editor_saves_entries_without_a_comment_as_a_plain_id(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Ban test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('ban.txt', ''),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->set('newBanId', 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')
            ->call('addBanEntry')
            ->assertSet('banEntries.0.id', 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')
            ->assertSet('banEntries.0.comment', '')
            ->call('saveBan')
            ->assertHasNoErrors();

        $saved = Storage::disk('dayz')->get($project->revisions()->latest('revision_number')->firstOrFail()->storage_path);
        $this->assertSame("AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA\n", $saved);
    }

    public function test_priority_txt_editor_splits_id_and_comment(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Priority test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('priority.txt', "BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB //VIP hráč\n"),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('priorityEntries.0.id', 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB')
            ->assertSet('priorityEntries.0.comment', 'VIP hráč');
    }

    public function test_event_groups_xml_has_a_structured_editor_and_saves_relative_object_settings(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Event groups test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><eventgroupdef><group name="Train_Test"><child type="StaticObj_Wreck_Train_742_Red_DE" deloot="0" lootmax="3" lootmin="1" x="0" z="0" a="78.123" y="1.9"/></group></eventgroupdef>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('cfgeventgroups.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('visualKind', 'event-groups')
            ->assertSet('eventGroups.0.name', 'Train_Test')
            ->assertSee('Skupinové eventy')
            ->assertSee('Relativní posun objektu')
            ->set('xmlValues./eventgroupdef[1]/group[1]/child[1]@lootmax', 4)
            ->call('saveEventGroups')
            ->assertHasNoErrors()
            ->set('newEventChildTypes.0', 'Land_Train_Wagon_Box_DE')
            ->call('addEventGroupChild', 0)
            ->assertHasNoErrors();

        $latest = $project->revisions()->latest('revision_number')->firstOrFail();
        $this->assertSame(3, $latest->revision_number);
        $saved = Storage::disk('dayz')->get($latest->storage_path);
        $this->assertStringContainsString('lootmax="4"', $saved);
        $this->assertStringContainsString('type="Land_Train_Wagon_Box_DE"', $saved);
    }

    public function test_event_spawns_xml_has_a_grouped_editor_and_saves_world_position(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Event spawns test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><eventposdef><event name="StaticSantaCrash"><pos x="5587.466" z="2063.353" a="78.123"/></event></eventposdef>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('cfgeventspawns.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('visualKind', 'event-spawns')
            ->assertSet('eventSpawns.0.name', 'StaticSantaCrash')
            ->assertSee('Umístění eventů')
            ->assertSee('Světové souřadnice')
            ->set('xmlValues./eventposdef[1]/event[1]/pos[1]@x', 6000)
            ->set('xmlValues./eventposdef[1]/event[1]/pos[1]@a', 90)
            ->call('saveEventSpawns')
            ->assertHasNoErrors();

        $saved = Storage::disk('dayz')->get($project->revisions()->latest('revision_number')->firstOrFail()->storage_path);
        $this->assertStringContainsString('x="6000"', $saved);
        $this->assertStringContainsString('a="90"', $saved);
    }

    public function test_type_query_parameter_opens_editor_with_the_matching_entry_selected(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        app(DayzDemoSeeder::class)->seedFor($user);
        $project = Project::query()->where('user_id', $user->id)->where('name', 'Chernarus Survival')->firstOrFail();

        $this->actingAs($user)
            ->get("/admin/projects/{$project->id}/configuration?type=AKM")
            ->assertOk()
            ->assertSee('<strong>AKM</strong>', false)
            ->assertSee('Výskyt: Military');
    }

    public function test_event_query_parameter_opens_editor_with_the_matching_entry_selected(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        app(DayzDemoSeeder::class)->seedFor($user);
        $project = Project::query()->where('user_id', $user->id)->where('name', 'Chernarus Survival')->firstOrFail();

        $this->actingAs($user)
            ->get("/admin/projects/{$project->id}/configuration?event=InfectedArmy")
            ->assertOk()
            ->assertSee('<strong>InfectedArmy</strong>', false);
    }

    public function test_types_xml_entry_can_be_removed_from_the_visual_editor(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Remove type test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><types><type name="AKM"><nominal>8</nominal></type><type name="Static_FrozenScientist_DE"><nominal>1</nominal></type></types>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->call('selectType', 'Static_FrozenScientist_DE')
            ->assertSet('selectedType', 'Static_FrozenScientist_DE')
            ->call('removeType')
            ->assertSet('selectedType', null)
            ->assertDontSee('Static_FrozenScientist_DE');

        $saved = Storage::disk('dayz')->get($project->revisions()->latest('revision_number')->firstOrFail()->storage_path);
        $this->assertStringNotContainsString('Static_FrozenScientist_DE', $saved);
        $this->assertStringContainsString('AKM', $saved);
    }

    public function test_events_xml_has_a_searchable_select_and_edit_one_editor(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Events test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        $xml = '<events><event name="StaticHeliCrash"><nominal>1</nominal><min>0</min><max>2</max><lifetime>3600</lifetime><restock>0</restock><saferadius>100</saferadius><distanceradius>100</distanceradius><cleanupradius>100</cleanupradius><flags deletable="0" init_random="1" remove_damaged="0"/><position>fixed</position><limit>mixed</limit><active>1</active><children><child lootmax="0" lootmin="0" max="1" min="1" type="Wreck_UH1Y"/></children></event></events>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('events.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('visualKind', 'events')
            ->assertSee('Vyhledej event')
            ->call('selectEvent', 'StaticHeliCrash')
            ->assertSet('selectedEvent', 'StaticHeliCrash')
            ->assertSet('eventForm.nominal', 1)
            ->set('eventForm.nominal', 5)
            ->call('saveEvent')
            ->assertHasNoErrors();

        $saved = Storage::disk('dayz')->get($project->revisions()->latest('revision_number')->firstOrFail()->storage_path);
        $this->assertStringContainsString('<nominal>5</nominal>', $saved);
    }

    public function test_large_generated_map_xml_uses_safe_map_mode_instead_of_thousands_of_inputs(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Generated map file test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><map><group name="Town"><pos x="100" z="200" a="90"/></group></map>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('mapgrouppos.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('visualKind', 'map-file')
            ->assertSet('xmlFields', [])
            ->assertSee('mapový datový soubor')
            ->assertSee('Otevřít mapový editor')
            ->set('mode', 'raw')
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

    public function test_editable_files_list_only_shows_the_latest_revision_per_filename(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Dedup test',
            'platform' => 'playstation',
            'map' => 'chernarusplus',
        ]);
        // Two separate imports of the same filename (e.g. re-uploaded instead of edited in place)
        // must still collapse to a single dropdown entry showing only the newest revision.
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('globals.xml', '<globals><var name="AnimalMaxCount" value="100"/></globals>'),
            $user,
        );
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('globals.xml', '<globals><var name="AnimalMaxCount" value="200"/></globals>'),
            $user,
        );

        $this->actingAs($user);
        $files = Livewire::test(EditConfiguration::class, ['record' => $project->id])->instance()->editableFiles();

        $globalsEntries = array_filter($files, fn ($label) => str_starts_with($label, 'globals.xml'));
        $this->assertCount(1, $globalsEntries);
        $this->assertStringContainsString('revize #2', reset($globalsEntries));
    }
}
