<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

final class NullFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return in_array($spec, ['null', 'not_null'], true);
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        if ($spec === 'null') {
            $query->whereNull($column);
            return;
        }

        $query->whereNotNull($column);
    }
}
