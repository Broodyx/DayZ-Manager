<?php

namespace App\Services\PlatformDetection;

use App\Models\Project;
use Illuminate\Validation\ValidationException;

final readonly class PlatformCompatibility
{
    public function __construct(private PlatformDetector $detector) {}

    public function assertEditable(Project $project, string $content, array $paths = []): void
    {
        if (! in_array($project->platform, ['playstation', 'xbox'], true)) {
            return;
        }

        $result = $this->detector->detect($content, $paths);
        if ($result->platform !== 'steam') {
            return;
        }

        throw ValidationException::withMessages([
            'rawContent' => 'Konfigurace obsahuje PC-only prvky, které nejsou podporované na '
                .ucfirst($project->platform).': '.implode(', ', $result->reasons),
        ]);
    }
}
