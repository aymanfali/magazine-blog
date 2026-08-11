<?php

declare(strict_types=1);

namespace App\Services\QueryBuilder;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class PaginationManager
{
    public function paginate(Builder $query, Request $request, int $perPage = 10): LengthAwarePaginator
    {
        $size = $request->integer('per_page', $perPage);
        $size = min(max($size, 1), 100);

        return $query->paginate($size)->withQueryString();
    }
}
