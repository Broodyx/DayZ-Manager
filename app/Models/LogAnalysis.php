<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAnalysis extends Model
{
    protected $fillable = [
        'project_id', 'created_by', 'storage_path', 'findings',
        'total_lines', 'matched_lines', 'critical_count', 'warning_count',
        'source_timestamp',
        'source_filename',
    ];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'total_lines' => 'integer',
            'matched_lines' => 'integer',
            'critical_count' => 'integer',
            'warning_count' => 'integer',
            'source_timestamp' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
