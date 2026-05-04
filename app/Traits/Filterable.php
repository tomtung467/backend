<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait Filterable
 *
 * Provides filtering capability for Eloquent models
 * Usage: Model::filter($filters)->get();
 */
trait Filterable
{
    /**
     * Apply filters to the query
     */
    public function scopeFilter(Builder $query, $filters)
    {
        $filters = is_array($filters) ? $filters : [];

        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            // Snake case column name
            $column = str_replace('.', '_', $key);

            // Try to call scope method first (scopeFilterName)
            $scopeMethod = 'scope' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $scopeMethod)) {
                $query->{str_replace('scope', '', lcfirst($scopeMethod))}($value);
                continue;
            }

            // Simple equality check
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } elseif (strpos($value, '%') !== false) {
                $query->where($column, 'like', $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * Search across multiple columns
     */
    public function scopeSearch(Builder $query, $keyword, array $columns = [])
    {
        if (empty($keyword) || empty($columns)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }
}
