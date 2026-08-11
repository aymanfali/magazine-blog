<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

final class BooleanFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return $spec === 'boolean';
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        $bool = $this->parseBoolean($value);
        $query->where($column, $bool);
    }

    protected function parseBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) ((int) $value);
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
