<?php

namespace App\Services\Revision;

use App\Models\ConfigurationRevision;
use App\Models\Project;
use App\Models\User;
use App\Services\Xml\XmlValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;
use Throwable;

final readonly class ConfigurationRevisionEditor
{
    public function __construct(private XmlValidator $xmlValidator) {}

    public function content(ConfigurationRevision $revision): string
    {
        if (! Storage::disk('dayz')->exists($revision->storage_path)) {
            throw new RuntimeException('Soubor této revize nebyl v úložišti nalezen.');
        }

        $extension = $this->editableExtension($revision);
        $content = Storage::disk('dayz')->get($revision->storage_path);
        $this->ensureEditorSize($content);

        return $extension === 'json' ? $this->formatJson($content) : $content;
    }

    public function save(
        Project $project,
        ConfigurationRevision $sourceRevision,
        string $content,
        ?string $changeSummary,
        User $user,
    ): ConfigurationRevision {
        if ($project->user_id !== $user->id || $sourceRevision->project_id !== $project->id) {
            throw new RuntimeException('Vybraná konfigurace nepatří přihlášenému uživateli.');
        }

        $extension = $this->editableExtension($sourceRevision);
        $this->ensureEditorSize($content);
        $this->validate($extension, $content);

        $path = "{$project->id}/revisions/".Str::uuid().'.'.$extension;
        Storage::disk('dayz')->put($path, $content);

        try {
            return DB::transaction(function () use (
                $project,
                $sourceRevision,
                $content,
                $path,
                $changeSummary,
                $user,
            ): ConfigurationRevision {
                $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
                $revisionNumber = ((int) $lockedProject->revisions()->max('revision_number')) + 1;

                return $lockedProject->revisions()->create([
                    'configuration_import_id' => $sourceRevision->configuration_import_id,
                    'revision_number' => $revisionNumber,
                    'storage_path' => $path,
                    'sha256' => hash('sha256', $content),
                    'change_summary' => filled($changeSummary)
                        ? $changeSummary
                        : "Úprava revize #{$sourceRevision->revision_number}",
                    'created_by' => $user->id,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('dayz')->delete($path);

            throw $exception;
        }
    }

    public function downloadName(ConfigurationRevision $revision): string
    {
        $originalName = $revision->configurationImport?->original_filename;
        $extension = pathinfo($originalName ?: $revision->storage_path, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName ?: 'configuration', PATHINFO_FILENAME);

        return "{$baseName}-revision-{$revision->revision_number}.{$extension}";
    }

    private function editableExtension(ConfigurationRevision $revision): string
    {
        $originalName = $revision->configurationImport?->original_filename;
        $extension = strtolower(pathinfo($originalName ?: $revision->storage_path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['xml', 'json', 'cfg', 'txt'], true)) {
            throw ValidationException::withMessages([
                'content' => 'Podporované editovatelné soubory jsou XML, JSON, CFG a TXT. ZIP nejprve importujte jako jednotlivé soubory.',
            ]);
        }

        return $extension;
    }

    private function ensureEditorSize(string $content): void
    {
        $maxBytes = (int) config('dayz.max_editor_size', 10240) * 1024;
        if (strlen($content) > $maxBytes) {
            throw ValidationException::withMessages([
                'content' => 'Soubor je pro webový editor příliš velký. Limit nastavuje MAX_EDITOR_SIZE.',
            ]);
        }
    }

    private function validate(string $extension, string $content): void
    {
        if ($extension === 'xml') {
            $result = $this->xmlValidator->validate($content);
            if (! $result->valid) {
                $messages = array_map(
                    static fn (array $error): string => sprintf(
                        'Řádek %d: %s',
                        (int) ($error['line'] ?? 0),
                        $error['message'] ?? 'Neplatné XML.',
                    ),
                    $result->errors,
                );

                throw ValidationException::withMessages(['content' => $messages]);
            }

            return;
        }

        if (in_array($extension, ['cfg', 'txt'], true)) {
            return;
        }

        try {
            json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw ValidationException::withMessages([
                'content' => 'Neplatný JSON: '.$exception->getMessage(),
            ]);
        }
    }

    private function formatJson(string $content): string
    {
        try {
            return json_encode(
                json_decode($content, true, flags: JSON_THROW_ON_ERROR),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ) ?: $content;
        } catch (JsonException) {
            return $content;
        }
    }
}
