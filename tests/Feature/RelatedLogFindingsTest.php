<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\EditConfiguration;
use App\Models\LogAnalysis;
use App\Models\Project;
use App\Models\User;
use App\Services\Import\ConfigurationImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RelatedLogFindingsTest extends TestCase
{
    use RefreshDatabase;

    private function seedAnalysis(Project $project, User $user, array $findings): void
    {
        LogAnalysis::query()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'storage_path' => 'log-analyses/test.log',
            'findings' => $findings,
            'total_lines' => 10,
            'matched_lines' => count($findings),
            'critical_count' => 0,
            'warning_count' => count($findings),
        ]);
    }

    public function test_types_xml_editor_shows_a_still_unresolved_log_finding_with_a_fix_button(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Related findings test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/><type name="Static_FrozenScientist_DE"/></types>'),
            $user,
        );
        $this->seedAnalysis($project, $user, [
            ['kind' => 'type-does-not-exist', 'title' => "Položka 'Static_FrozenScientist_DE' v types.xml neexistuje ve hře", 'target' => 'Static_FrozenScientist_DE', 'severity' => 'critical', 'link' => 'types-editor', 'count' => 1, 'detail' => '', 'action' => null, 'example' => '', 'first_timestamp' => null],
        ]);

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSee('Stav souboru')
            ->assertSee("Položka 'Static_FrozenScientist_DE' v types.xml neexistuje ve hře")
            ->assertSee('Bezpečně odebrat');
    }

    public function test_finding_disappears_after_the_flagged_type_is_removed(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Related findings test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('types.xml', '<types><type name="AKM"/><type name="Static_FrozenScientist_DE"/></types>'),
            $user,
        );
        $this->seedAnalysis($project, $user, [
            ['kind' => 'type-does-not-exist', 'title' => "Položka 'Static_FrozenScientist_DE' v types.xml neexistuje ve hře", 'target' => 'Static_FrozenScientist_DE', 'severity' => 'critical', 'link' => 'types-editor', 'count' => 1, 'detail' => '', 'action' => null, 'example' => '', 'first_timestamp' => null],
        ]);

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSee("Static_FrozenScientist_DE' v types.xml neexistuje")
            ->call('removeTypeByName', 'Static_FrozenScientist_DE')
            ->assertDontSee("Static_FrozenScientist_DE' v types.xml neexistuje");
    }

    public function test_findings_for_a_different_file_are_not_shown(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Related findings test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('messages.xml', '<messages></messages>'),
            $user,
        );
        $this->seedAnalysis($project, $user, [
            ['kind' => 'type-does-not-exist', 'title' => "Položka 'Foo' v types.xml neexistuje ve hře", 'target' => 'Foo', 'severity' => 'critical', 'link' => 'types-editor', 'count' => 1, 'detail' => '', 'action' => null, 'example' => '', 'first_timestamp' => null],
        ]);

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertDontSee("Položka 'Foo' v types.xml neexistuje ve hře");
    }
}
