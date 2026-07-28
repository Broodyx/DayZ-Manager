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
     * Files that live at the server profile root (alongside serverDZ.cfg, outside the
     * mission folder) on every platform — never nested under db/, env/ or a mission folder.
     */
    private const PROFILE_ROOT_FILES = [
        'serverdz.cfg', 'ban.txt', 'whitelist.txt', 'priority.txt', 'dayzsettings.xml', 'beserver_x64.cfg',
    ];

    /** Files that live in the mission's db/ subfolder, confirmed against real PC and PS server dumps. */
    private const DB_FILES = ['types.xml', 'events.xml', 'globals.xml', 'messages.xml', 'economy.xml'];

    /**
     * Official Bohemia maps whose default mission folder name is well documented. Community
     * maps (Namalsk, Deerisle, …) name their mission folder per server, so those are
     * intentionally left out rather than guessed — nesting only happens for these.
     */
    private const OFFICIAL_MISSION_FOLDERS = [
        'chernarusplus' => 'dayzOffline.chernarusplus',
        'enoch' => 'dayzOffline.enoch',
        'sakhal' => 'dayzOffline.sakhal',
    ];

    /**
     * Builds a ZIP of the latest revision of every file in the project, laid out in the
     * folder structure the real server expects, and marks all of them downloaded — same
     * bookkeeping as every other download entry point, so the "nestaženo" tracking stays
     * accurate regardless of which download button was used.
     *
     * PC servers nest mission files under dayzOffline.<map>/ (confirmed against a real PC
     * server backup); PlayStation/Xbox keep everything flat with just db/ and env/
     * subfolders (confirmed against a real PS server/Nitrado export) since consoles don't
     * expose a separate mission-folder path the same way. Profile-level files (serverDZ.cfg,
     * ban.txt, whitelist.txt, priority.txt, dayzsettings.xml) always stay at the ZIP root.
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

        $missionFolder = $project->platform === 'steam'
            ? (self::OFFICIAL_MISSION_FOLDERS[strtolower((string) $project->map)] ?? null)
            : null;

        $filenames = [];
        $usedNames = [];
        foreach ($revisions as $revision) {
            if (! Storage::disk('dayz')->exists($revision->storage_path)) {
                continue;
            }
            $filename = $revision->configurationImport?->original_filename ?? basename($revision->storage_path);
            $filename = basename(str_replace('\\', '/', $filename));

            $entryName = $this->entryPath($filename, $missionFolder);
            $suffix = 1;
            while (isset($usedNames[$entryName])) {
                $entryName = $this->entryPath($filename, $missionFolder, ++$suffix);
            }
            $usedNames[$entryName] = true;

            $zip->addFromString($entryName, Storage::disk('dayz')->get($revision->storage_path));
            $filenames[] = $entryName;
        }
        $zip->close();

        ConfigurationRevision::query()->whereIn('id', $revisions->pluck('id'))->update(['downloaded_at' => now()]);

        return ['path' => $zipPath, 'filenames' => $filenames];
    }

    private function entryPath(string $filename, ?string $missionFolder, int $suffix = 1): string
    {
        $name = $suffix > 1 ? $filename.'.'.$suffix : $filename;
        $lower = strtolower($filename);

        if (in_array($lower, self::PROFILE_ROOT_FILES, true) || str_starts_with($lower, 'dayzps-settings-')) {
            return $name;
        }

        $relative = match (true) {
            in_array($lower, self::DB_FILES, true) => 'db/'.$name,
            str_ends_with($lower, '_territories.xml') => 'env/'.$name,
            default => $name,
        };

        return $missionFolder ? $missionFolder.'/'.$relative : $relative;
    }
}
