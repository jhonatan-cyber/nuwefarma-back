<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasAdvancedScopes
{
    /**
     * Laravel 12+ advanced query scopes with type hints
     */
    public function scopeWhereLike(Builder $query, string $column, string $value): Builder
    {
        return $query->where($column, 'like', "%{$value}%");
    }

    public function scopeWhereInMultiple(Builder $query, string $column, array $values): Builder
    {
        return $query->whereIn($column, $values);
    }

    public function scopeWhereBetweenDates(Builder $query, string $column, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween($column, [$startDate, $endDate]);
    }

    public function scopeWhereJsonContains(Builder $query, string $column, mixed $value): Builder
    {
        return $query->whereJsonContains($column, $value);
    }

    public function scopeWhereJsonLength(Builder $query, string $column, int $length, string $operator = '>='): Builder
    {
        return $query->whereJsonLength($column, $operator, $length);
    }

    public function scopeWithCountRelations(Builder $query, array $relations): Builder
    {
        foreach ($relations as $relation) {
            $query->withCount($relation);
        }

        return $query;
    }

    public function scopeWithSumRelations(Builder $query, array $relations): Builder
    {
        foreach ($relations as $relation => $column) {
            $query->withSum($relation, $column);
        }

        return $query;
    }

    public function scopeOrderByMultiple(Builder $query, array $orders): Builder
    {
        foreach ($orders as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    public function scopeWhereHasRelation(Builder $query, string $relation, ?callable $callback = null): Builder
    {
        return $query->whereHas($relation, $callback);
    }

    public function scopeWhereDoesntHaveRelation(Builder $query, string $relation, ?callable $callback = null): Builder
    {
        return $query->whereDoesntHave($relation, $callback);
    }

    public function scopeWhereNullRelations(Builder $query, array $relations): Builder
    {
        foreach ($relations as $relation) {
            $query->whereNull($relation);
        }

        return $query;
    }

    public function scopeWhereNotNullRelations(Builder $query, array $relations): Builder
    {
        foreach ($relations as $relation) {
            $query->whereNotNull($relation);
        }

        return $query;
    }

    public function scopeSearchInColumns(Builder $query, string $term, array $columns): Builder
    {
        return $query->where(function ($query) use ($term, $columns) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    public function scopeFilterByDateRange(Builder $query, string $column, ?string $from = null, ?string $to = null): Builder
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }
        if ($to) {
            $query->where($column, '<=', $to);
        }

        return $query;
    }

    public function scopeFilterByNumericRange(Builder $query, string $column, ?float $min = null, ?float $max = null): Builder
    {
        if ($min !== null) {
            $query->where($column, '>=', $min);
        }
        if ($max !== null) {
            $query->where($column, '<=', $max);
        }

        return $query;
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $method = 'scope'.Str::studly($filter);
            if (method_exists($this, $method)) {
                $this->$method($query, $value);
            }
        }

        return $query;
    }

    public function scopeApplySorting(Builder $query, array $sort): Builder
    {
        $column = $sort['column'] ?? 'created_at';
        $direction = $sort['direction'] ?? 'desc';

        if (in_array($column, $this->getSortableColumns())) {
            $query->orderBy($column, $direction);
        }

        return $query;
    }

    public function scopeApplyPagination(Builder $query, int $page = 1, int $perPage = 15): Builder
    {
        return $query->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Laravel 12+ advanced aggregation scopes
     */
    public function scopeWithAggregations(Builder $query, array $aggregations): Builder
    {
        foreach ($aggregations as $type => $columns) {
            foreach ($columns as $column => $alias) {
                $query->selectRaw("{$type}({$column}) as {$alias}");
            }
        }

        return $query;
    }

    public function scopeWithGroupBy(Builder $query, array $columns): Builder
    {
        return $query->groupBy($columns);
    }

    public function scopeWithHaving(Builder $query, string $column, string $operator, mixed $value): Builder
    {
        return $query->having($column, $operator, $value);
    }

    /**
     * Laravel 12+ JSON and array operations
     */
    public function scopeWhereJsonPath(Builder $query, string $path, mixed $value): Builder
    {
        return $query->whereJsonPath($path, $value);
    }

    public function scopeWhereJsonOverlaps(Builder $query, string $column, array $value): Builder
    {
        return $query->whereJsonOverlaps($column, $value);
    }

    public function scopeWhereJsonContainsKey(Builder $query, string $column, string $key): Builder
    {
        return $query->whereJsonContainsKey($column, $key);
    }

    /**
     * Laravel 12+ full-text search
     */
    public function scopeFullTextSearch(Builder $query, string $term, array $columns = []): Builder
    {
        if (empty($columns)) {
            $columns = $this->getSearchableColumns();
        }

        return $query->whereRaw("MATCH({implode(',', $columns)}) AGAINST(? IN BOOLEAN MODE)", [$term]);
    }

    /**
     * Laravel 12+ window functions
     */
    public function scopeWithWindowFunction(Builder $query, string $function, string $column, string $alias): Builder
    {
        return $query->selectRaw("{$function}({$column}) OVER () as {$alias}");
    }

    public function scopeWithRanking(Builder $query, string $column, string $alias = 'rank'): Builder
    {
        return $query->selectRaw("RANK() OVER (ORDER BY {$column} DESC) as {$alias}");
    }

    /**
     * Laravel 12+ CTE (Common Table Expressions)
     */
    public function scopeWithCTE(Builder $query, string $name, string $subquery): Builder
    {
        return $query->withExpression($name, $subquery);
    }

    /**
     * Helper methods for advanced scopes
     */
    protected function getSortableColumns(): array
    {
        return [
            'id', 'created_at', 'updated_at', 'name', 'title', 'status',
            'price', 'amount', 'quantity', 'total',
        ];
    }

    protected function getSearchableColumns(): array
    {
        return [
            'name', 'title', 'description', 'content', 'notes',
        ];
    }

    /**
     * Laravel 12+ advanced caching scopes
     */
    public function scopeCacheQuery(Builder $query, string $key, \DateTimeInterface|\DateInterval|int $ttl = 3600): Builder
    {
        return $query->cacheFor($ttl, $key);
    }

    public function scopeCacheTags(Builder $query, array $tags): Builder
    {
        return $query->cacheTags($tags);
    }

    /**
     * Laravel 12+ performance optimization scopes
     */
    public function scopeOptimizedSelect(Builder $query, ?array $columns = null): Builder
    {
        if ($columns === null) {
            $columns = $this->getOptimizedSelectColumns();
        }

        return $query->select($columns);
    }

    public function scopeOptimizedWith(Builder $query, array $relations): Builder
    {
        $optimizedRelations = [];
        foreach ($relations as $relation => $columns) {
            if (is_numeric($relation)) {
                $optimizedRelations[] = $columns;
            } else {
                $optimizedRelations[$relation] = function ($query) use ($columns) {
                    $query->select($columns);
                };
            }
        }

        return $query->with($optimizedRelations);
    }

    protected function getOptimizedSelectColumns(): array
    {
        return [
            'id', 'name', 'status', 'created_at', 'updated_at',
        ];
    }
}
