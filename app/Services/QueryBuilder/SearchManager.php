<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder;

use App\Services\QueryBuilder\Contracts\SearchInterface;
use Illuminate\Database\Eloquent\Builder;

final class SearchManager implements SearchInterface
{
    public function apply(Builder $query, mixed $searchConfig, ?string $term): void
    {
        if (empty($term) || !is_array($searchConfig)) {
            return;
        }

        $query->where(function (Builder $q) use ($searchConfig, $term) {
            foreach ($searchConfig as $field) {
                if (is_string($field)) {
                    $q->orWhere($field, 'like', "%{$term}%");
                    continue;
                }

                if (is_array($field) && isset($field['relation'])) {
                    $relation = $field['relation'];
                    $column = $field['column'] ?? 'name';
                    $operator = $field['operator'] ?? 'like';

                    $q->orWhereHas($relation, function (Builder $qr) use ($column, $operator, $term) {
                        if ($operator === 'like') {
                            $qr->where($column, 'like', "%{$term}%");
                        } else {
                            $qr->where($column, $operator, $term);
                        }
                    });
                }
            }
        });
    }
}
