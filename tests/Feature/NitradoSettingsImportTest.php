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

class NitradoSettingsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_nitrado_settings_export_imports_successfully_and_is_described(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Nitrado test', 'platform' => 'playstation', 'map' => 'chernarusplus', 'hosting' => 'Nitrado',
        ]);
        $json = json_encode(['meta' => ['game' => 'dayzps', 'timestamp' => 1785175342], 'settings' => [
            'hostname' => 'Broody_DEV', 'enableWhitelist' => '1', 'enableMouseAndKeyboard' => '1',
        ]]);
        $import = app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('dayzps-settings-1785175342.json', $json),
            $user,
        );

        $this->assertSame('valid', $import->validation_status);

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSee('Export nastavení z Nitrado ovládacího panelu');
    }

    public function test_project_hosting_field_accepts_free_text(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Hosting test', 'platform' => 'unknown', 'map' => 'chernarusplus', 'hosting' => 'GTX Gaming',
        ]);

        $this->assertSame('GTX Gaming', $project->fresh()->hosting);
    }
}
