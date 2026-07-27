<?php

namespace Tests\Feature;

use App\Filament\Pages\MapEditor;
use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MapEditorSpawnWarningsTest extends TestCase
{
    use RefreshDatabase;

    private function seedSpawnPoints(Project $project, User $user, string $content): void
    {
        $path = "{$project->id}/test/cfgplayerspawnpoints.xml";
        Storage::disk('dayz')->put($path, $content);
        $sha256 = hash('sha256', $content);
        $import = ConfigurationImport::query()->create([
            'project_id' => $project->id,
            'sha256' => $sha256,
            'original_filename' => 'cfgplayerspawnpoints.xml',
            'storage_path' => $path,
            'detected_platform' => $project->platform,
            'detection_confidence' => 65,
            'validation_status' => 'valid',
            'imported_at' => now(),
        ]);
        ConfigurationRevision::query()->create([
            'project_id' => $project->id,
            'revision_number' => 1,
            'configuration_import_id' => $import->id,
            'storage_path' => $path,
            'sha256' => $sha256,
            'change_summary' => 'test',
            'created_by' => $user->id,
        ]);
    }

    public function test_map_editor_warns_when_a_spawn_mode_has_no_points(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedSpawnPoints($project, $user, <<<'XML'
<playerspawnpoints>
    <fresh><generator_posbubbles><group name="DevSpawn"><pos x="1" z="2"/></group></generator_posbubbles></fresh>
    <hop><generator_posbubbles><group name="Empty"/></generator_posbubbles></hop>
    <travel><generator_posbubbles><group name="Empty"/></generator_posbubbles></travel>
</playerspawnpoints>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnPointWarnings')
            ->assertSet('spawnPointWarnings', [
                ['mode' => 'hop', 'label' => 'HOP · změna serveru'],
                ['mode' => 'travel', 'label' => 'TRAVEL · cestovní přesun'],
            ]);
    }

    public function test_map_editor_has_no_warning_when_every_mode_has_a_point(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create(['is_admin' => true]);
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Chernarus test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedSpawnPoints($project, $user, <<<'XML'
<playerspawnpoints>
    <fresh><generator_posbubbles><group name="A"><pos x="1" z="2"/></group></generator_posbubbles></fresh>
    <hop><generator_posbubbles><group name="B"><pos x="3" z="4"/></group></generator_posbubbles></hop>
    <travel><generator_posbubbles><group name="C"><pos x="5" z="6"/></group></generator_posbubbles></travel>
</playerspawnpoints>
XML);

        Livewire::actingAs($user)->test(MapEditor::class, [])
            ->set('projectId', $project->id)
            ->call('loadSpawnPointWarnings')
            ->assertSet('spawnPointWarnings', []);
    }
}
