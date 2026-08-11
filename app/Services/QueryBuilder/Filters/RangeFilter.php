<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder\Filters;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class RangeFilter implements FilterInterface
{
    public function supports(mixed $spec): bool
    {
        return is_array($spec) && isset($spec['from'], $spec['to']);
    }

    public function apply(Builder $query, string $column, mixed $spec, mixed $value): void
    {
        $fromKey = is_array($spec) && isset($spec['from']) ? $spec['from'] : 'from';
        $toKey = is_array($spec) && isset($spec['to']) ? $spec['to'] : 'to';

        $from = null;
        $to = null;

        if (is_array($value)) {
            $from = $value[$fromKey] ?? $value["{$column}_start"] ?? $value["{$column}_from"] ?? $value['from'] ?? $value['start'] ?? null;
            $to = $value[$toKey] ?? $value["{$column}_end"] ?? $value["{$column}_to"] ?? $value['to'] ?? $value['end'] ?? null;
        }

        if (!is_null($from) && $from !== '') {
            $d = $this->parseDate($from);
            if ($d) {
                $query->whereDate($column, '>=', $d->startOfDay());
            }
        }

        if (!is_null($to) && $to !== '') {
            $d = $this->parseDate($to);
            if ($d) {
                $query->whereDate($column, '<=', $d->endOfDay());
            }
        }
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        try {
            return Carbon::parse((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
