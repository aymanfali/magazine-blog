<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder;

use App\Services\QueryBuilder\Contracts\SortInterface;
use Illuminate\Database\Eloquent\Builder;

final class SortManager implements SortInterface
{
    public function apply(Builder $query, mixed $sortConfig, ?string $key, ?string $direction): void
    {
        if ($sortConfig instanceof QueryBuilderConfig) {
            $allowed = $sortConfig->getSort();
            $default = $sortConfig->getDefaultSort();
        } else {
            $allowed = is_array($sortConfig) ? $sortConfig : [];
            $default = (new QueryBuilderConfig(is_array($sortConfig) ? $sortConfig : []))->getDefaultSort();
        }

        $direction = $this->sanitizeDirection($direction);

        if ($key && in_array($key, $allowed, true)) {
            $query->orderBy($key, $direction);
            return;
        }

        $query->orderBy($default, 'desc');
    }

    private function sanitizeDirection(?string $dir): string
    {
        $dir = strtolower((string) ($dir ?? ''));
        return in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';
    }
}

