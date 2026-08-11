<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder;

final class QueryBuilderConfig
{
    protected array $raw;

    public function __construct(array $config = [])
    {
        $this->raw = $config;
    }

    /**
     * Return a new config instance with array merged into existing raw config.
     */
    public function merge(array $config): self
    {
        return new self(array_replace_recursive($this->raw, $config));
    }

    /**
     * Return the underlying raw config array.
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    private function arrayValue(string $key): array
    {
        $value = $this->raw[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    public function getSelect(): array
    {
        return $this->arrayValue('select');
    }

    public function getWith(): array
    {
        return $this->arrayValue('with');
    }

    public function getSearch(): array
    {
        return $this->arrayValue('search');
    }

    public function getFilters(): array
    {
        return $this->arrayValue('filters');
    }

    public function getSort(): array
    {
        return $this->arrayValue('sort');
    }

    public function getDefaultSort(): string
    {
        $sort = $this->raw['default_sort'] ?? null;

        return is_string($sort) && $sort !== ''
            ? $sort
            : 'created_at';
    }
}
