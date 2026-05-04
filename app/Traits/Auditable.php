<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait Auditable
 *
 * Automatically track created_by, updated_by, deleted_by
 * Requires: created_by, updated_by, deleted_by columns in migration
 *
 * @mixin Model
 * @method static void creating(\Closure $callback)
 * @method static void updating(\Closure $callback)
 * @method static void deleting(\Closure $callback)
 * @method BelongsTo belongsTo(string $related, string $foreignKey = null, string $ownerKey = null, string $relation = null)
 */
trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable(): void
    {
        // Track user when creating
        static::creating(function ($model) {
            if (Auth::check() && !isset($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        // Track user when updating
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // Track user when deleting (soft delete)
        static::deleting(function ($model) {
            if (Auth::check() && method_exists($model, 'trashed')) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }

    /**
     * Get the user who created this record
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record
     */
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
