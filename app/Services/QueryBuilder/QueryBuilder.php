<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class QueryBuilder
{
    private Builder $query;
    private Request $request;
    private QueryBuilderConfig $config;

    public function __construct(Builder $query, Request $request, QueryBuilderConfig $config)
    {
        $this->query = $query;
        $this->request = $request;
        $this->config = $config;
    }

    public static function for(Builder $query): self
    {
        return new self($query, request(), new QueryBuilderConfig());
    }

    public function withConfig(array $config): self
    {
        $this->config = $this->config->merge($config);

        return $this;
    }

    public function apply(): Builder
    {
        // apply select & eager loads
        $select = $this->config->getSelect();
        if (!empty($select)) {
            $this->query->select($select);
        }

        $with = $this->config->getWith();
        if (!empty($with)) {
            $this->query->with($with);
        }

        // search
        $searchManager = new SearchManager();
        $searchManager->apply($this->query, $this->config->getSearch(), $this->request->get('search'));

        // filters
        $filterManager = new FilterManager();
        $filterManager->apply($this->query, $this->config, $this->request->all());

        // sorts
        $sortManager = new SortManager();
        $sortManager->apply($this->query, $this->config, $this->request->get('sort_key'), $this->request->get('sort_direction'));

        return $this->query;
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        $pagination = new PaginationManager();
        return $pagination->paginate($this->apply(), $this->request, $perPage);
    }
}
