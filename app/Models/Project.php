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
        'game_version', 'hosting', 'description',
        'ftp_protocol', 'ftp_host', 'ftp_port', 'ftp_username', 'ftp_password', 'ftp_root_path',
    ];

    protected $hidden = ['ftp_password'];

    protected function casts(): array
    {
        return [
            'platform_confidence' => 'integer',
            'ftp_port' => 'integer',
            'ftp_password' => 'encrypted',
        ];
    }

    public function hasFtpConnection(): bool
    {
        return filled($this->ftp_host) && filled($this->ftp_username) && filled($this->ftp_password);
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
        return array_column($this->undeployedFileRevisions(), 'filename');
    }

    /**
     * Same as undeployedFiles(), but with the revision info needed to link a direct download.
     *
     * @return list<array{filename: string, revision_id: int, revision_number: int}>
     */
    public function undeployedFileRevisions(): array
    {
        return $this->revisions
            ->sortByDesc('revision_number')
            ->unique(fn (ConfigurationRevision $revision): string => strtolower(basename(str_replace('\\', '/', $revision->configurationImport?->original_filename ?? $revision->storage_path))))
            ->filter(fn (ConfigurationRevision $revision): bool => $revision->downloaded_at === null)
            ->map(fn (ConfigurationRevision $revision): array => [
                'filename' => $revision->configurationImport?->original_filename ?? basename($revision->storage_path),
                'revision_id' => $revision->id,
                'revision_number' => $revision->revision_number,
            ])
            ->values()
            ->all();
    }

    public function getUndeployedFilesCountAttribute(): int
    {
        return count($this->undeployedFiles());
    }
}
