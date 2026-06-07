<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClinicalRecordVersion extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version_number',
        'changed_by',
        'change_reason',
        'changed_fields',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'old_values' => 'encrypted:array',
        'new_values' => 'encrypted:array',
        'version_number' => 'integer',
        'created_at' => 'datetime',
    ];

    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
