<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder;

use App\Services\QueryBuilder\Contracts\FilterInterface;
use App\Services\QueryBuilder\Filters\BasicFilter;
use App\Services\QueryBuilder\Filters\BooleanFilter;
use App\Services\QueryBuilder\Filters\CallbackFilter;
use App\Services\QueryBuilder\Filters\InFilter;
use App\Services\QueryBuilder\Filters\NullFilter;
use App\Services\QueryBuilder\Filters\RangeFilter;
use App\Services\QueryBuilder\Filters\RelationFilter;
use Illuminate\Database\Eloquent\Builder;

final class FilterManager
{
    /** @var FilterInterface[] */
    private array $strategies;

    public function __construct(?array $strategies = null)
    {
        $this->strategies = $strategies ?? [
            new CallbackFilter(),
            new RangeFilter(),
            new RelationFilter(),
            new InFilter(),
            new NullFilter(),
            new BooleanFilter(),
            new BasicFilter(),
        ];
    }

    public function apply(Builder $query, QueryBuilderConfig $config, array $params): void
    {
        foreach ($config->getFilters() as $column => $spec) {
            $value = $this->resolveFilterValue($column, $spec, $params);

            if ($value === null || $value === '') {
                continue;
            }

            foreach ($this->strategies as $strategy) {
                if ($strategy->supports($spec)) {
                    $strategy->apply($query, $column, $spec, $value);
                    break;
                }
            }
        }
    }

    private function resolveFilterValue(string $column, mixed $spec, array $params): mixed
    {
        if (is_callable($spec)) {
            return $params;
        }

        if (is_array($spec) && isset($spec['from'], $spec['to'])) {
            return $params;
        }

        return $params[$column] ?? null;
    }
}
