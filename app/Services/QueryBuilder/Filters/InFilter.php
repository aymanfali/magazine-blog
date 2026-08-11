<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Illuminate\Database\Eloquent\Builder;

final class InFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return in_array($spec, ['in', 'not_in'], true);
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        $values = $this->normalize($value);

        if (empty($values)) {
            return;
        }

        if ($spec === 'in') {
            $query->whereIn($column, $values);
            return;
        }

        $query->whereNotIn($column, $values);
    }

    protected function normalize(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== '' && !is_null($v)));
        }

        if (!is_string($value)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn($v) => $v !== ''));
    }
}
