<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\QueryBuilder\QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $config = [
            'select' => [
                'id',
                'name',
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

                'parent' => [
                    'relation' => 'parent',
                    'column' => 'id',
                    'operator' => '=',
                ],
            ],

            'sort' => [
                'name',
                'is_active',
                'created_at',
                'updated_at',
            ],

            'default_sort' => 'created_at',
        ];

        $query = Category::query();

        if (request()->boolean('only_trashed')) {
            $query = $query->onlyTrashed();
        } elseif (request()->boolean('with_trashed')) {
            $query = $query->withTrashed();
        }

        $builder = QueryBuilder::for($query)
            ->withConfig($config);

        $perPage = request()->integer('per_page', 5);

        $categories = request()->boolean('paginate', true)
            ? $builder->paginate($perPage)
            : $builder->apply()->get();

            return Inertia::render('categories/Index', [
            'categories' => $categories,

            'filters' => request()->all([
                'search',
                'name',
                'is_active',
                'created_from',
                'created_to',
                'created_at_start',
                'created_at_end',
                'updated_from',
                'updated_to',
                'updated_at_start',
                'updated_at_end',
                'deleted_from',
                'deleted_to',
                'deleted_at_start',
                'deleted_at_end',
                'parent',
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

    public function show(Request $request, int $id): Response
    {
        $query = $request->boolean('with_trashed')
            ? Category::withTrashed()
            : Category::query();

        $category = $query->with(['parent', 'children'])->findOrFail($id);

        return Inertia::render('Category/Show', [
            'category' => $category,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCategory($request);

        $data['slug'] = $this->buildSlug($data);
        $data['uuid'] = Str::uuid()->toString();

        if ($request->user() !== null && ! array_key_exists('user_id', $data)) {
            $data['user_id'] = $request->user()->id;
        }

        $category = Category::create($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Category created.'),
        ]);

        return to_route('categories.show', $category);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateCategory($request, $category);

        $data['slug'] = $this->buildSlug($data, $category);

        $category->update($data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Category updated.'),
        ]);

        return to_route('categories.show', $category);
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($id);

        if (request()->boolean('force')) {
            $category->forceDelete();

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Category permanently deleted.'),
            ]);

            return redirect()->back();
        }

        $category->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Category deleted.'),
        ]);

        return redirect()->back();
    }

    public function restore(int $id): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        $category->restore();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Category restored.'),
        ]);

        return redirect()->back();
    }

    protected function validateCategory(Request $request, ?Category $category = null): array
    {
        $ignoreId = $category?->id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];

        if ($category !== null) {
            $rules['parent_id'][] = Rule::notIn([$category->id]);
        }

        return $request->validate($rules);
    }

    protected function buildSlug(array $data, ?Category $category = null): string
    {
        $slug = isset($data['slug']) && trim($data['slug']) !== ''
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        $original = $slug;
        $counter = 1;

        while (Category::where('slug', $slug)
            ->when($category, fn($query) => $query->where('id', '<>', $category->id))
            ->exists()
        ) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }
}
