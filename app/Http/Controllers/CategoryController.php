<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\QueryBuilder\QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Category $category): Response
    {
        $config = [
            'select' => [
                'id',
                'image',
                'name',
                'description',
                'slug',
                'is_active',
                'parent_id',
                'created_at',
                'updated_at',
                'deleted_at',
            ],

            'with' => [
                'parent',
            ],

            'search' => [
                'name',
            ],

            'filters' => [
                'name' => 'like',

                'parent_id' => 'equals',

                'is_active' => 'boolean',

                'created_at' => [
                    'from' => 'created_from',
                    'to' => 'created_to',
                ],

                'updated_at' => [
                    'from' => 'updated_from',
                    'to' => 'updated_to',
                ],

                'deleted_at' => [
                    'from' => 'deleted_from',
                    'to' => 'deleted_to',
                ],
            ],

            'sort' => [
                'name',
                'parent_name',
                'is_active',
                'created_at',
                'updated_at',
            ],

            'default_sort' => 'created_at',
        ];

        $query = Category::query();

        if (request()->boolean('only_trashed')) {
            $query->onlyTrashed();
        } elseif (request()->boolean('with_trashed')) {
            $query->withTrashed();
        }

        $builder = QueryBuilder::for($query)
            ->withConfig($config);

        $perPage = request()->integer('per_page', 5);

        $categories = request()->boolean('paginate', true)
            ? $builder->paginate($perPage)
            : $builder->apply()->get();

        $parentCategories = Category::query()
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,

            'filters' => request()->all([
                'search',
                'name',
                'parent_id',
                'is_active',

                'created_from',
                'created_to',

                'updated_from',
                'updated_to',

                'deleted_from',
                'deleted_to',

                'only_trashed',
                'with_trashed',
            ]),

            'sort' => request()->only([
                'sort_key',
                'sort_direction',
            ]),

            'trashed' => [
                'with_trashed' => request()->boolean('with_trashed'),
                'only_trashed' => request()->boolean('only_trashed'),
            ],
        ]);
    }

    public function show(Category $category): Response
    {
        $category->load([
            'parent',
            'children',
        ]);

        return Inertia::render('categories/Show', [
            'category' => $category,
        ]);
    }

    public function create(Category $category)
    {
        $category->load([
            'parent',
            'children',
        ]);

        $parentCategories = Category::query()
            ->whereHas('children')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('categories/Create', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = $this->categoryService->create(
            $request->validated(),
            $request->user()?->id
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('frontend.messages.category_created'),
        ]);

        return to_route(
            'dash.categories.index',
        );
    }

    public function edit(Category $category)
    {
        $category->load([
            'parent',
            'children',
        ]);

        $parentCategories = Category::query()
            ->whereNull('parent_id')
            ->when(
                $category,
                fn($query) => $query->whereKeyNot($category->getKey())
            )
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function update(
        CategoryRequest $request,
        Category $category
    ): RedirectResponse {
        $category = $this->categoryService->update(
            $category,
            $request->validated()
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('frontend.messages.category_updated'),
        ]);

        return to_route(
            'dash.categories.index',
        );
    }

    public function destroy(Category $category): RedirectResponse
    {
        if (request()->boolean('force')) {
            $this->categoryService->forceDelete($category);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('frontend.messages.category_permanently_deleted'),
            ]);

            return back();
        }

        $this->categoryService->delete($category);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('frontend.messages.category_deleted'),
        ]);

        return back();
    }

    public function restore(Category $category): RedirectResponse
    {
        $this->categoryService->restore($category);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('frontend.messages.category_restored'),
        ]);

        return back();
    }
}
