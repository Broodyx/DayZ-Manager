<?php

namespace App\Services\Dayz;

use App\Models\Project;

/**
 * Maps a configuration filename to the relative path it actually lives at on a real
 * DayZ server, confirmed against real PC and PlayStation server backups — shared by
 * anything that packages or pushes files to match the real folder structure (the
 * "download all" ZIP builder and the FTP push-to-server action).
 */
final class ServerFileLayout
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

    public function missionFolderFor(Project $project): ?string
    {
        return $project->platform === 'steam'
            ? (self::OFFICIAL_MISSION_FOLDERS[strtolower((string) $project->map)] ?? null)
            : null;
    }

    /** Relative path (mission folder + db/env subfolder as applicable) for a given filename. */
    public function relativePath(string $filename, Project $project): string
    {
        return $this->relativePathForMissionFolder($filename, $this->missionFolderFor($project));
    }

    /**
     * $filename decides which folder the file belongs in; pass $displayName when the actual
     * written name needs to differ (e.g. a ".2" suffix to dedupe) without changing that
     * classification — "types.xml.2" should still resolve to db/, not root.
     */
    public function relativePathForMissionFolder(string $filename, ?string $missionFolder, ?string $displayName = null): string
    {
        $lower = strtolower($filename);
        $name = $displayName ?? $filename;

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
