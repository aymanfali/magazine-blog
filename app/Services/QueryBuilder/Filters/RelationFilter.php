<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

final class RelationFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return is_array($spec) && isset($spec['relation']);
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        $relation = $spec['relation'] ?? null;
        $relColumn = $spec['column'] ?? 'name';
        $operator = $spec['operator'] ?? '=';

        if (!is_string($relation) || $relation === '') {
            return;
        }

        $query->whereHas($relation, function (Builder $q) use ($relColumn, $operator, $value) {
            if ($operator === 'like') {
                $q->where($relColumn, 'like', "%{$value}%");
            } else {
                $q->where($relColumn, $operator, $value);
            }
        });
    }
}
