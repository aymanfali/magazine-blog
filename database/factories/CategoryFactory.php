<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(
            $this->faker->numberBetween(1, 3),
            true
        );

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => $this->faker->sentence(),
            'image' => null,
            'is_active' => $this->faker->boolean(80),
            'parent_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'is_active' => true,
        ]);
    }

    public function child(Category $parent): static
    {
        return $this->state([
            'parent_id' => $parent->getKey(),
        ]);
    }
}
