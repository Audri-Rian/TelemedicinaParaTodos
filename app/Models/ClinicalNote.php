<?php

namespace App\Models;

use App\Models\Concerns\HasClinicalVersioning;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalNote extends Model
{
    use HasClinicalVersioning;

    /** @use HasFactory<\Database\Factories\ClinicalNoteFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected array $versionedFields = ['title', 'content', 'is_private', 'category', 'tags'];

    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'title',
        'content',
        'is_private',
        'category',
        'tags',
        'version',
        'parent_id',
        'metadata',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_DIAGNOSIS = 'diagnosis';

    public const CATEGORY_TREATMENT = 'treatment';

    public const CATEGORY_FOLLOW_UP = 'follow_up';

    public const CATEGORY_OTHER = 'other';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointments::class, 'appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
