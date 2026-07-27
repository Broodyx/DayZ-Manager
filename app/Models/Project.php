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
}
