<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationRevision extends Model
{
    protected $fillable = [
        'project_id', 'configuration_import_id', 'revision_number',
        'storage_path', 'sha256', 'change_summary', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'downloaded_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function configurationImport(): BelongsTo
    {
        return $this->belongsTo(ConfigurationImport::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
