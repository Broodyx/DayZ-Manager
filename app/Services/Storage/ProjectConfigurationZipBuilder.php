<?php

namespace App\Services\Storage;

use App\Models\ConfigurationRevision;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

final class ProjectConfigurationZipBuilder
{
    /**
     * Builds a ZIP of the latest revision of every file in the project and marks all of
     * them downloaded — same bookkeeping as every other download entry point, so the
     * "nestaženo" tracking stays accurate regardless of which download button was used.
     *
     * @return array{path: string, filenames: list<string>}
     */
    public function build(Project $project): array
    {
        $revisions = $project->revisions()
            ->with('configurationImport')
            ->orderByDesc('revision_number')
            ->get()
            ->unique(fn (ConfigurationRevision $revision): string => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
            ->values();

        if ($revisions->isEmpty()) {
            throw new RuntimeException('Server zatím nemá žádné nahrané soubory ke stažení.');
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'dzcfg').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ZIP archiv se nepodařilo vytvořit.');
        }

        $filenames = [];
        $usedNames = [];
        foreach ($revisions as $revision) {
            if (! Storage::disk('dayz')->exists($revision->storage_path)) {
                continue;
            }
            $filename = $revision->configurationImport?->original_filename ?? basename($revision->storage_path);
            $filename = str_replace('\\', '/', $filename);
            $entryName = $filename;
            $suffix = 1;
            while (isset($usedNames[$entryName])) {
                $entryName = $filename.'.'.(++$suffix);
            }
            $usedNames[$entryName] = true;

            $zip->addFromString($entryName, Storage::disk('dayz')->get($revision->storage_path));
            $filenames[] = $entryName;
        }
        $zip->close();

        ConfigurationRevision::query()->whereIn('id', $revisions->pluck('id'))->update(['downloaded_at' => now()]);

        return ['path' => $zipPath, 'filenames' => $filenames];
    }
}
