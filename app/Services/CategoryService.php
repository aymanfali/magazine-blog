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

            unset($data['remove_image']);

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

            /*
            |--------------------------------------------------------------------------
            | Normalize remove_image
            |--------------------------------------------------------------------------
            */

            $removeImage = $this->toBoolean(
                $data['remove_image'] ?? false
            );

            unset($data['remove_image']);

            /*
            |--------------------------------------------------------------------------
            | Keep the old image path
            |--------------------------------------------------------------------------
            */

            $oldImage = $category->image;

            /*
            |--------------------------------------------------------------------------
            | New image
            |--------------------------------------------------------------------------
            */

            $hasNewImage = isset($data['image'])
                && $data['image'] instanceof UploadedFile;
            if ($hasNewImage) {
                $data['image'] = $data['image']->store(
                    'categories',
                    'public'
                );
            } elseif ($removeImage) {
                $data['image'] = null;
            } else {
                unset($data['image']);
            }

            /*
            |--------------------------------------------------------------------------
            | Update database
            |--------------------------------------------------------------------------
            */

            $category->update($data);

            /*
            |--------------------------------------------------------------------------
            | Delete old physical image
            |--------------------------------------------------------------------------
            |
            | Delete the old file when:
            |
            | 1. The user explicitly removed it, OR
            | 2. The user uploaded a replacement.
            |
            */

            if ($oldImage && ($removeImage || $hasNewImage)) {
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

    /*
    |--------------------------------------------------------------------------
    | Image Handling
    |--------------------------------------------------------------------------
    */

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

    protected function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize path
        |--------------------------------------------------------------------------
        |
        | Database should normally contain:
        |
        | categories/example.jpg
        |
        | But this also protects against values such as:
        |
        | /storage/categories/example.jpg
        | storage/categories/example.jpg
        |
        */

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Boolean Normalization
    |--------------------------------------------------------------------------
    */

    protected function toBoolean(mixed $value): bool
    {
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Slug
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Parent Validation
    |--------------------------------------------------------------------------
    */

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
                'parent_id' => __(
                    'The selected parent category is invalid.'
                ),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Self-parent
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Descendant-parent
        |--------------------------------------------------------------------------
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

    protected function isDescendantOf(
        Category $category,
        Category $ancestor
    ): bool {
        $visited = [];

        while ($category->parent_id !== null) {
            if (isset($visited[$category->getKey()])) {
                return false;
            }

            $visited[$category->getKey()] = true;

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
