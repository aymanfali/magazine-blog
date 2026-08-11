<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_category_index_returns_paginated_results()
    {
        Category::factory()->count(15)->create();

        $response = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'per_page' => 5, 'page' => 1]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 5)
            ->where('categories.total', 15)
            ->where('categories.current_page', 1)
        );
    }

    public function test_category_index_search_filter()
    {
        Category::factory()->create(['name' => 'Technology']);
        Category::factory()->create(['name' => 'Sports']);

        $response = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'search' => 'Tech']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Technology')
        );
    }

    public function test_category_index_name_column_filter()
    {
        Category::factory()->create(['name' => 'Gadgets']);
        Category::factory()->create(['name' => 'Fashion']);

        $response = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'name' => 'Gadg']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Gadgets')
        );
    }

    public function test_category_index_is_active_filter()
    {
        Category::factory()->create(['name' => 'Active Cat', 'is_active' => true]);
        Category::factory()->create(['name' => 'Inactive Cat', 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'is_active' => '1']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Active Cat')
        );

        $response0 = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'is_active' => '0']));

        $response0->assertOk();
        $response0->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Inactive Cat')
        );
    }

    public function test_category_index_only_trashed_filter()
    {
        $active = Category::factory()->create(['name' => 'Active Item']);
        $trashed = Category::factory()->create(['name' => 'Trashed Item']);
        $trashed->delete();

        $response = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'only_trashed' => '1']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Trashed Item')
        );
    }

    public function test_category_index_with_trashed_filter()
    {
        Category::factory()->create(['name' => 'Active Item']);
        $trashed = Category::factory()->create(['name' => 'Trashed Item']);
        $trashed->delete();

        $response = $this->actingAs($this->user)
            ->get(route('dash.categories.index', ['locale' => 'en', 'with_trashed' => '1']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('categories/Index')
            ->has('categories.data', 2)
        );
    }

    public function test_category_soft_delete()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('dash.categories.destroy', ['locale' => 'en', 'id' => $category->id]));

        $response->assertRedirect();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_restore()
    {
        $category = Category::factory()->create();
        $category->delete();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        $response = $this->actingAs($this->user)
            ->post(route('dash.categories.restore', ['locale' => 'en', 'id' => $category->id]));

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    public function test_category_force_delete()
    {
        $category = Category::factory()->create();
        $category->delete();

        $response = $this->actingAs($this->user)
            ->delete(route('dash.categories.destroy', ['locale' => 'en', 'id' => $category->id, 'force' => '1']));

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
