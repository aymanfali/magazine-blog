<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SortInterface
{
    /**
     * Apply sorting to the query.
     */
    public function apply(Builder $query, mixed $sortConfig, ?string $key, ?string $direction): void;
}
