<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface SearchInterface
{
    /**
     * Apply search to the query.
     */
    public function apply(Builder $query, mixed $searchConfig, ?string $term): void;
}
