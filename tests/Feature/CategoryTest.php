<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_categories(): void
    {
        Category::factory()->count(4)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => [['id', 'name', 'slug']]])
            ->assertJsonCount(4, 'data');
    }

    public function test_index_returns_categories_ordered_by_name(): void
    {
        Category::factory()->create(['name' => 'Zebra', 'slug' => 'zebra']);
        Category::factory()->create(['name' => 'Apple', 'slug' => 'apple']);
        Category::factory()->create(['name' => 'Mango', 'slug' => 'mango']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('Apple', $data[0]['name']);
        $this->assertEquals('Mango', $data[1]['name']);
        $this->assertEquals('Zebra', $data[2]['name']);
    }

    public function test_index_returns_empty_when_no_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }
}
