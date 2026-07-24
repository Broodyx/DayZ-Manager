<?php

namespace Database\Seeders;

use App\Models\ConfigurationImport;
use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DayzDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->oldest('id')->first();
        if (! $user) {
            throw new RuntimeException('Nejprve vytvořte uživatele pomocí php artisan make:filament-user.');
        }

        $this->seedFor($user);
    }

    public function seedFor(User $user): void
    {
        $samples = [
            [
                'name' => 'Chernarus Survival',
                'platform' => 'playstation',
                'confidence' => 65,
                'map' => 'ChernarusPlus',
                'version' => '1.26',
                'description' => 'Ukázkový komunitní PlayStation server zaměřený na survival.',
                'filename' => 'types.xml',
                'content' => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<types>
    <type name="AKM">
        <nominal>8</nominal>
        <lifetime>28800</lifetime>
        <restock>1800</restock>
        <min>4</min>
        <quantmin>-1</quantmin>
        <quantmax>-1</quantmax>
        <cost>100</cost>
        <flags count_in_cargo="0" count_in_hoarder="0" count_in_map="1" count_in_player="0" crafted="0" deloot="0"/>
        <category name="weapons"/>
        <usage name="Military"/>
    </type>
</types>
XML,
            ],
            [
                'name' => 'Livonia Community',
                'platform' => 'xbox',
                'confidence' => 60,
                'map' => 'Enoch',
                'version' => '1.26',
                'description' => 'Ukázková konfigurace Xbox serveru na mapě Livonia.',
                'filename' => 'cfggameplay.json',
                'content' => <<<'JSON'
{
    "version": 121,
    "GeneralData": {
        "disableBaseDamage": false,
        "disableContainerDamage": false,
        "disableRespawnDialog": false
    },
    "PlayerData": {
        "disablePersonalLight": true,
        "spawnGearPresetFiles": []
    }
}
JSON,
            ],
            [
                'name' => 'Namalsk Modded',
                'platform' => 'steam',
                'confidence' => 85,
                'map' => 'Namalsk',
                'version' => '1.26',
                'description' => 'Ukázkový PC server s detekovatelnými Steam Workshop módy.',
                'filename' => 'server-settings.json',
                'content' => <<<'JSON'
{
    "hostname": "Namalsk Modded Demo",
    "launch": "-mod=@Namalsk;@Community-Framework",
    "workshop": "https://steamcommunity.com/sharedfiles/filedetails/?id=2289456201"
}
JSON,
            ],
        ];

        foreach ($samples as $sample) {
            $project = Project::query()->updateOrCreate(
                ['user_id' => $user->id, 'name' => $sample['name']],
                [
                    'platform' => $sample['platform'],
                    'platform_confidence' => $sample['confidence'],
                    'map' => $sample['map'],
                    'game_version' => $sample['version'],
                    'description' => $sample['description'],
                ],
            );

            $path = "{$project->id}/demo/{$sample['filename']}";
            Storage::disk('dayz')->put($path, $sample['content']);
            $sha256 = hash('sha256', $sample['content']);

            $import = ConfigurationImport::query()->updateOrCreate(
                ['project_id' => $project->id, 'sha256' => $sha256],
                [
                    'original_filename' => $sample['filename'],
                    'storage_path' => $path,
                    'detected_platform' => $sample['platform'],
                    'detection_confidence' => $sample['confidence'],
                    'validation_status' => 'valid',
                    'validation_errors' => null,
                    'imported_at' => now(),
                ],
            );

            ConfigurationRevision::query()->updateOrCreate(
                ['project_id' => $project->id, 'revision_number' => 1],
                [
                    'configuration_import_id' => $import->id,
                    'storage_path' => $path,
                    'sha256' => $sha256,
                    'change_summary' => 'Počáteční ukázková konfigurace',
                    'created_by' => $user->id,
                ],
            );
        }

        $this->command?->info('Vytvořeny 3 ukázkové DayZ projekty pro uživatele '.$user->email);
    }
}
