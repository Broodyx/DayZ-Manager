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

class WeatherEnableToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggling_enable_checkbox_and_saving_persists_it_to_the_raw_xml(): void
    {
        Storage::fake('dayz');
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id, 'name' => 'Weather bug test', 'platform' => 'playstation', 'map' => 'chernarusplus',
        ]);
        $xml = '<?xml version="1.0"?><weather reset="0" enable="0"><overcast><current actual="0.45" time="120" duration="240"/><limits min="0" max="1"/><timelimits min="600" max="900"/><changelimits min="0" max="1"/></overcast></weather>';
        app(ConfigurationImporter::class)->import(
            $project,
            UploadedFile::fake()->createWithContent('cfgweather.xml', $xml),
            $user,
        );

        $this->actingAs($user);
        Livewire::test(EditConfiguration::class, ['record' => $project->id])
            ->assertSet('visualKind', 'weather')
            ->assertSet('weatherForm.enable', false)
            ->set('weatherForm.enable', true)
            ->call('saveWeather')
            ->assertHasNoErrors();

        $saved = Storage::disk('dayz')->get($project->revisions()->latest('revision_number')->firstOrFail()->storage_path);
        $this->assertStringContainsString('enable="1"', $saved);
    }
}
