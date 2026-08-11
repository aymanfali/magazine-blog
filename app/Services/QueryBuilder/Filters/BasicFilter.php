<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

final class BasicFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return is_string($spec) && in_array($spec, ['equals', 'not_equals', 'like', '>', '>=', '<', '<=','between'], true);
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        if ($spec === 'between') {
            $values = is_array($value) ? array_values($value) : array_map('trim', explode(',', (string) $value));
            if (count($values) === 2) {
                $query->whereBetween($column, [$values[0], $values[1]]);
            }

            return;
        }

        match ($spec) {
            'equals' => $query->where($column, $value),
            'not_equals' => $query->where($column, '<>', $value),
            'like' => $query->where($column, 'like', "%{$value}%"),
            '>' => $query->where($column, '>', $value),
            '>=' => $query->where($column, '>=', $value),
            '<' => $query->where($column, '<', $value),
            '<=' => $query->where($column, '<=', $value),
            default => null,
        };
    }
}
