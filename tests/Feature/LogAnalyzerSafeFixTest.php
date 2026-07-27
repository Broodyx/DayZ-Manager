<?php

namespace Tests\Feature;

use App\Filament\Pages\LogAnalyzer;
use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LogAnalyzerSafeFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_remove_type_entry_deletes_the_type_and_creates_a_new_revision(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Log fix test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedFile($project, $user, 'types.xml', '<types><type name="AKM"><nominal>8</nominal></type><type name="ChristmasTree"><nominal>1</nominal></type></types>');

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->call('removeTypeEntry', 'ChristmasTree');

        $latest = $project->revisions()->latest('revision_number')->firstOrFail();
        $this->assertSame(2, $latest->revision_number);
        $saved = Storage::disk('dayz')->get($latest->storage_path);
        $this->assertStringNotContainsString('ChristmasTree', $saved);
        $this->assertStringContainsString('AKM', $saved);
    }

    public function test_remove_orphan_event_spawn_deletes_positions_and_creates_a_new_revision(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Log fix test', 'platform' => 'playstation', 'map' => 'ChernarusPlus',
        ]);
        $this->seedFile($project, $user, 'cfgeventspawns.xml', '<eventposdef><event name="StaticHeliCrash"><pos x="1" z="2" a="0"/></event><event name="VehicleTransitBus"><pos x="3" z="4" a="0"/></event></eventposdef>');

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->set('projectId', $project->id)
            ->call('removeOrphanEventSpawn', 'VehicleTransitBus');

        $latest = $project->revisions()->latest('revision_number')->firstOrFail();
        $this->assertSame(2, $latest->revision_number);
        $saved = Storage::disk('dayz')->get($latest->storage_path);
        $this->assertStringNotContainsString('VehicleTransitBus', $saved);
        $this->assertStringContainsString('StaticHeliCrash', $saved);
    }

    public function test_remove_type_entry_does_nothing_without_a_selected_project(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(LogAnalyzer::class)
            ->call('removeTypeEntry', 'ChristmasTree');

        $this->assertSame(0, ConfigurationRevision::query()->count());
    }

    private function seedFile(Project $project, User $user, string $filename, string $content): void
    {
        $path = "{$project->id}/test/{$filename}";
        Storage::disk('dayz')->put($path, $content);
        $sha256 = hash('sha256', $content);
        $import = ConfigurationImport::query()->create([
            'project_id' => $project->id,
            'sha256' => $sha256,
            'original_filename' => $filename,
            'storage_path' => $path,
            'detected_platform' => $project->platform,
            'detection_confidence' => 65,
            'validation_status' => 'valid',
            'imported_at' => now(),
        ]);
        ConfigurationRevision::query()->create([
            'project_id' => $project->id,
            'revision_number' => (int) $project->revisions()->max('revision_number') + 1,
            'configuration_import_id' => $import->id,
            'storage_path' => $path,
            'sha256' => $sha256,
            'change_summary' => 'test',
            'created_by' => $user->id,
        ]);
    }
}
