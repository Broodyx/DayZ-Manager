<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'platform', 'platform_confidence', 'map',
        'game_version', 'description',
    ];

    protected function casts(): array
    {
        return ['platform_confidence' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(ConfigurationImport::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ConfigurationRevision::class);
    }

    public function logAnalyses(): HasMany
    {
        return $this->hasMany(LogAnalysis::class);
    }

    /**
     * Filenames whose latest revision has never been downloaded — i.e. edited/imported
     * here but not yet pulled onto the actual game server by the user.
     *
     * @return list<string>
     */
    public function undeployedFiles(): array
    {
        return $this->revisions
            ->sortByDesc('revision_number')
            ->unique(fn (ConfigurationRevision $revision): string => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
            ->filter(fn (ConfigurationRevision $revision): bool => $revision->downloaded_at === null)
            ->map(fn (ConfigurationRevision $revision): string => $revision->configurationImport?->original_filename ?? basename($revision->storage_path))
            ->values()
            ->all();
    }

    public function getUndeployedFilesCountAttribute(): int
    {
        return count($this->undeployedFiles());
    }
}
