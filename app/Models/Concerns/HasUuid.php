<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the trait
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Scope to find by UUID
     */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where($this->getTable() . '.' . $this->getKeyName(), $uuid);
    }

    /**
     * Scope to find multiple UUIDs
     */
    public function scopeWhereUuids($query, array $uuids)
    {
        return $query->whereIn($this->getTable() . '.' . $this->getKeyName(), $uuids);
    }

    /**
     * Find model by UUID or fail
     */
    public static function findByUuidOrFail(string $uuid): self
    {
        return static::whereUuid($uuid)->firstOrFail();
    }

    /**
     * Find model by UUID
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::whereUuid($uuid)->first();
    }

    /**
     * Get route key name for UUID
     */
    public function getRouteKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Resolve route binding for UUID
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->whereUuid($value)->first();
    }
}
