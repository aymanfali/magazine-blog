<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;

final class CallbackFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return is_callable($spec) || $spec instanceof Closure;
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        if (!is_callable($spec)) {
            return;
        }

        $spec($query, $value);
    }
}
