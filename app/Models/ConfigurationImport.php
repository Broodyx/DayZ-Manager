<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConfigurationImport extends Model
{
    protected $fillable = [
        'project_id', 'original_filename', 'storage_path', 'sha256',
        'detected_platform', 'detection_confidence', 'validation_status',
        'validation_errors', 'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'detection_confidence' => 'integer',
            'validation_errors' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function revisions(): HasMany { return $this->hasMany(ConfigurationRevision::class); }
}
