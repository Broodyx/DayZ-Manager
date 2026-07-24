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

            if ($sample['name'] === 'Chernarus Survival') {
                $weatherContent = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<weather reset="0" enable="1">
    <overcast><current actual="0.35" time="900" duration="1800"/><limits min="0.1" max="0.8" change="0.25"/></overcast>
    <fog><current actual="0.12" time="600" duration="1200"/><limits min="0" max="0.45" change="0.15"/></fog>
    <rain><current actual="0.05" time="600" duration="1200"/><limits min="0" max="0.65" change="0.2"/></rain>
    <wind><magnitude current="0.3" time="600" duration="1200"/><direction current="0.5" time="600" duration="1200"/><limits magnitude_min="0" magnitude_max="1" magnitude_change="0.25" direction_change="0.4"/></wind>
    <snowfall><current actual="0" time="600" duration="1200"/><limits min="0" max="0.2" change="0.1"/></snowfall>
    <storm density="0.15" threshold="0.7" timeout="120" cooldown="300"/>
</weather>
XML;
                $weatherPath = "{$project->id}/demo/cfgweather.xml";
                Storage::disk('dayz')->put($weatherPath, $weatherContent);
                $weatherHash = hash('sha256', $weatherContent);
                $weatherImport = ConfigurationImport::query()->updateOrCreate(
                    ['project_id' => $project->id, 'sha256' => $weatherHash],
                    [
                        'original_filename' => 'cfgweather.xml', 'storage_path' => $weatherPath,
                        'detected_platform' => $sample['platform'], 'detection_confidence' => $sample['confidence'],
                        'validation_status' => 'valid', 'validation_errors' => null, 'imported_at' => now(),
                    ],
                );
                ConfigurationRevision::query()->updateOrCreate(
                    ['project_id' => $project->id, 'revision_number' => 2],
                    [
                        'configuration_import_id' => $weatherImport->id, 'storage_path' => $weatherPath,
                        'sha256' => $weatherHash, 'change_summary' => 'Ukázkové řízení počasí', 'created_by' => $user->id,
                    ],
                );
            }
        }

        $this->command?->info('Vytvořeny 3 ukázkové DayZ projekty pro uživatele '.$user->email);
    }
}
