<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Services\QueryBuilder\QueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryBuilderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_by_name()
    {
        Category::factory()->create(['name' => 'Alpha']);
        Category::factory()->create(['name' => 'Beta']);

        request()->merge(['search' => 'Alpha']);

        $results = QueryBuilder::for(Category::query())
            ->withConfig(['search' => ['name']])
            ->apply()
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Alpha', $results->first()->name);
    }

    public function test_equals_filter()
    {
        Category::factory()->create(['name' => 'One', 'is_active' => 1]);
        Category::factory()->create(['name' => 'Two', 'is_active' => 0]);

        request()->merge(['is_active' => 1]);

        $results = QueryBuilder::for(Category::query())
            ->withConfig(['filters' => ['is_active' => 'equals']])
            ->apply()
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('One', $results->first()->name);
    }

    public function test_relation_filter()
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        Category::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);
        Category::factory()->create(['name' => 'Orphan', 'parent_id' => null]);

        request()->merge(['parent' => (string) $parent->id]);

        $results = QueryBuilder::for(Category::query())
            ->withConfig(['filters' => ['parent' => ['relation' => 'parent', 'column' => 'id', 'operator' => '=']]])
            ->apply()
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Child', $results->first()->name);
    }

    public function test_sorting_and_pagination()
    {
        Category::factory()->create(['name' => 'A']);
        Category::factory()->create(['name' => 'B']);
        Category::factory()->create(['name' => 'C']);

        request()->merge(['sort_key' => 'name', 'sort_direction' => 'desc', 'per_page' => 1]);

        $paginator = QueryBuilder::for(Category::query())
            ->withConfig(['sort' => ['name'], 'default_sort' => 'created_at'])
            ->paginate();

        $this->assertEquals(3, $paginator->total());
        $this->assertEquals(1, $paginator->perPage());
        $this->assertEquals('C', $paginator->items()[0]->name);
    }

    public function test_config_with_no_filter_parameters_returns_all_results()
    {
        Category::factory()->count(3)->create();

        request()->merge([]);

        $results = QueryBuilder::for(Category::query())
            ->withConfig([
                'filters' => [
                    'is_active' => 'equals',
                    'created_at' => [
                        'from' => 'created_from',
                        'to' => 'created_to',
                    ],
                    'parent' => [
                        'relation' => 'parent',
                        'column' => 'id',
                        'operator' => '=',
                    ],
                ],
            ])
            ->apply()
            ->get();

        $this->assertCount(3, $results);
    }

    public function test_relation_filter_does_not_apply_when_parameter_is_missing()
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);
        Category::factory()->create(['parent_id' => null]);

        request()->merge([]);

        $results = QueryBuilder::for(Category::query())
            ->withConfig([
                'filters' => [
                    'parent' => [
                        'relation' => 'parent',
                        'column' => 'id',
                        'operator' => '=',
                    ],
                ],
            ])
            ->apply()
            ->get();

        $this->assertCount(3, $results);
    }

    public function test_search_uses_configured_searchable_fields()
    {
        Category::factory()->create(['name' => 'Orange', 'description' => 'Fruit']);
        Category::factory()->create(['name' => 'Bottle', 'description' => 'Container']);

        request()->merge(['search' => 'Fruit']);

        $results = QueryBuilder::for(Category::query())
            ->withConfig(['search' => ['name', 'description']])
            ->apply()
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Orange', $results->first()->name);
    }

    public function test_per_page_value_is_honored_when_pagination_is_requested()
    {
        Category::factory()->count(5)->create();

        request()->merge(['per_page' => 2]);

        $paginator = QueryBuilder::for(Category::query())
            ->withConfig(['sort' => ['id'], 'default_sort' => 'id'])
            ->paginate();

        $this->assertEquals(2, $paginator->perPage());
        $this->assertCount(2, $paginator->items());
    }
}
