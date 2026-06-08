<?php

namespace App\Models\Concerns;

use App\Models\ClinicalRecordVersion;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

trait HasClinicalVersioning
{
    /** Pending diff captured during updating, written after successful save. */
    private array $_pendingVersion = [];

    /** Reason set by the caller before saving. */
    private ?string $_versionChangeReason = null;

    /** MAX(version_number) captured in updating to avoid a second query in updated. */
    private int $_pendingMaxVersion = 1;

    public static function bootHasClinicalVersioning(): void
    {
        static::created(function (self $model) {
            $fields = $model->versionedFields ?? [];
            $newValues = collect($fields)
                ->mapWithKeys(fn ($field) => [$field => $model->getAttribute($field)])
                ->all();

            $model->versions()->create([
                'version_number' => 1,
                'changed_by' => Auth::id() ?? $model->doctor?->user_id,
                'change_reason' => null,
                'changed_fields' => $fields,
                'old_values' => [],
                'new_values' => $newValues,
            ]);
        });

        // Capture dirty state BEFORE save (getOriginal only available here).
        static::updating(function (self $model) {
            $tracked = $model->versionedFields ?? [];
            $dirty = array_values(array_intersect($tracked, array_keys($model->getDirty())));

            if (empty($dirty)) {
                return;
            }

            $model->_pendingMaxVersion = $model->versions()->max('version_number') ?? 1;

            $model->_pendingVersion = [
                'changed_fields' => $dirty,
                'old_values' => collect($dirty)
                    ->mapWithKeys(fn ($f) => [$f => $model->getOriginal($f)])
                    ->all(),
                'new_values' => collect($dirty)
                    ->mapWithKeys(fn ($f) => [$f => $model->getAttribute($f)])
                    ->all(),
                'change_reason' => $model->_versionChangeReason,
            ];
        });

        // Write version AFTER successful save — never creates orphan rows.
        static::updated(function (self $model) {
            if (empty($model->_pendingVersion)) {
                return;
            }

            $changedBy = Auth::id();
            if ($changedBy === null && ! app()->runningInConsole()) {
                throw new \RuntimeException('Versionamento de registro clínico requer contexto autenticado.');
            }

            $model->versions()->create([
                'version_number' => $model->_pendingMaxVersion + 1,
                'changed_by' => $changedBy ?? $model->doctor?->user_id,
                ...$model->_pendingVersion,
            ]);

            $model->_pendingVersion = [];
            $model->_versionChangeReason = null;
        });
    }

    public function setVersionChangeReason(string $reason): void
    {
        $this->_versionChangeReason = $reason;
    }

    public function versions(): MorphMany
    {
        return $this->morphMany(ClinicalRecordVersion::class, 'versionable')
            ->orderBy('version_number');
    }
}
