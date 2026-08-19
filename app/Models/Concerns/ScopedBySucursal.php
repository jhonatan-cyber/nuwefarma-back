<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopedBySucursal
{
    protected static function bootScopedBySucursal(): void
    {
        static::addGlobalScope('sucursal', function (Builder $builder): void {
            if (! app()->bound('request')) {
                return;
            }

            $user = request()->user('sanctum');

            if (! $user || $user->isAdmin()) {
                return;
            }

            $column = $builder->getModel()->qualifyColumn('sucursal_id');

            if (! $user->sucursal_id) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($column, $user->sucursal_id);
        });
    }
}
