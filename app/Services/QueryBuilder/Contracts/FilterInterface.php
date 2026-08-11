<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface FilterInterface
{
    /**
     * Determine if this filter supports the given specification.
     *
     * @param mixed $spec
     */
    public function supports(mixed $spec): bool;

    /**
     * Apply filter to the query.
     *
     * @param Builder $query
     * @param string $column
     * @param mixed $spec
     * @param mixed $value
     */
    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void;
}
