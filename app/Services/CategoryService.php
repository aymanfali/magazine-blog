<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function create(
        array $data,
        ?string $userId = null
    ): Category {
        return DB::transaction(function () use ($data, $userId) {
            $this->validateParent($data['parent_id'] ?? null);

            $data['slug'] = $this->buildSlug($data);

            if ($userId !== null) {
                $data['user_id'] ??= $userId;
            }

            $data = $this->handleImage($data);

            return Category::create($data);
        });
    }


    public function update(
        Category $category,
        array $data
    ): Category {
        return DB::transaction(function () use ($category, $data) {
            $this->validateParent(
                $data['parent_id'] ?? null,
                $category
            );
            
            $data['slug'] = $this->buildSlug(
                $data,
                $category
            );

            $oldImage = $category->image;

            $data = $this->handleImage($data);

            $category->update($data);

            /*
             * Delete the old image only after the new image
             * has been successfully stored and the model updated.
             */
            if (
                isset($data['image']) &&
                $oldImage &&
                $oldImage !== $data['image']
            ) {
                $this->deleteImage($oldImage);
            }

            return $category->refresh();
        });
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function restore(Category $category): Category
    {
        if (! $category->trashed()) {
            return $category;
        }

        $category->restore();

        return $category->refresh();
    }

    public function forceDelete(Category $category): void
    {
        $image = $category->image;

        $category->forceDelete();

        if ($image) {
            $this->deleteImage($image);
        }
    }

    protected function handleImage(array $data): array
    {
        if (
            ! isset($data['image']) ||
            ! $data['image'] instanceof UploadedFile
        ) {
            unset($data['image']);

            return $data;
        }

        $data['image'] = $data['image']->store(
            'categories',
            'public'
        );

        return $data;
    }

    protected function deleteImage(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    protected function buildSlug(
        array $data,
        ?Category $category = null
    ): string {
        $slug = ! empty($data['slug'])
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        $original = $slug;
        $counter = 1;

        while (
            Category::query()
            ->where('slug', $slug)
            ->when(
                $category,
                fn($query) => $query->whereKeyNot(
                    $category->getKey()
                )
            )
            ->exists()
        ) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected function validateParent(
        ?string $parentId,
        ?Category $category = null
    ): void {
        if (! $parentId) {
            return;
        }

        $parent = Category::query()->find($parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => __('The selected parent category is invalid.'),
            ]);
        }

        /*
         * A category cannot be its own parent.
         */
        if (
            $category &&
            $parent->is($category)
        ) {
            throw ValidationException::withMessages([
                'parent_id' => __(
                    'A category cannot be its own parent.'
                ),
            ]);
        }

        /*
         * A category cannot use one of its descendants
         * as its parent.
         *
         * Example:
         *
         * X
         * └── Y
         *     └── Z
         *
         * X cannot have Y or Z as parent.
         */
        if (
            $category &&
            $this->isDescendantOf(
                $parent,
                $category
            )
        ) {
            throw ValidationException::withMessages([
                'parent_id' => __(
                    'A category cannot have one of its descendants as its parent.'
                ),
            ]);
        }
    }

    /**
     * Determine whether $category is a descendant of $ancestor.
     */
    protected function isDescendantOf(
        Category $category,
        Category $ancestor
    ): bool {
        $visited = [];

        while ($category->parent_id !== null) {
            /*
             * Safety guard against already-corrupted data.
             */
            if (isset($visited[$category->getKey()])) {
                return false;
            }

            $visited[$category->getKey()] = true;

            /*
             * We reached the category being updated.
             */
            if (
                (string) $category->parent_id ===
                (string) $ancestor->getKey()
            ) {
                return true;
            }

            $category = Category::query()->find(
                $category->parent_id
            );

            if (! $category) {
                break;
            }
        }

        return false;
    }
}
