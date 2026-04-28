<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_index_requires_user_id(): void
    {
        $response = $this->getJson('/api/v1/user/preferences/categories');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_index_returns_all_categories_with_is_preferred_flag(): void
    {
        $cat1 = Category::factory()->create(['name' => 'Tech', 'slug' => 'tech']);
        $cat2 = Category::factory()->create(['name' => 'Sports', 'slug' => 'sports']);
        $cat3 = Category::factory()->create(['name' => 'Health', 'slug' => 'health']);
        DB::table('user_category_preferences')->insert([
            ['user_id' => 1, 'category_id' => $cat1->id],
        ]);

        $response = $this->getJson('/api/v1/user/preferences/categories?user_id=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'is_preferred']]]);

        $data = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($data[$cat1->id]['is_preferred']);
        $this->assertFalse($data[$cat2->id]['is_preferred']);
        $this->assertFalse($data[$cat3->id]['is_preferred']);
    }

    public function test_index_returns_all_categories_as_not_preferred_when_user_has_none(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/user/preferences/categories?user_id=99');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $allNotPreferred = collect($response->json('data'))->every(fn($c) => $c['is_preferred'] === false);
        $this->assertTrue($allNotPreferred);
    }

    public function test_index_is_preferred_reflects_correct_user(): void
    {
        $category = Category::factory()->create();
        DB::table('user_category_preferences')->insert(['user_id' => 2, 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/user/preferences/categories?user_id=1');

        $response->assertOk();
        $this->assertFalse($response->json('data.0.is_preferred'));
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_store_requires_user_id(): void
    {
        $response = $this->postJson('/api/v1/user/preferences/categories', [
            'categories' => [1],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_requires_non_empty_categories_array(): void
    {
        $response = $this->postJson('/api/v1/user/preferences/categories', [
            'user_id'    => 1,
            'categories' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_requires_categories_to_be_array(): void
    {
        $response = $this->postJson('/api/v1/user/preferences/categories', [
            'user_id'    => 1,
            'categories' => 'not-an-array',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_saves_valid_category_preferences(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();

        $response = $this->postJson('/api/v1/user/preferences/categories', [
            'user_id'    => 1,
            'categories' => [$cat1->id, $cat2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.saved', 2);

        $this->assertDatabaseHas('user_category_preferences', ['user_id' => 1, 'category_id' => $cat1->id]);
        $this->assertDatabaseHas('user_category_preferences', ['user_id' => 1, 'category_id' => $cat2->id]);
    }

    public function test_store_silently_ignores_invalid_category_ids(): void
    {
        $valid = Category::factory()->create();

        $response = $this->postJson('/api/v1/user/preferences/categories', [
            'user_id'    => 1,
            'categories' => [$valid->id, 9999],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.saved', 1);
    }

    public function test_store_replaces_existing_preferences(): void
    {
        $cat1 = Category::factory()->create();
        $cat2 = Category::factory()->create();
        DB::table('user_category_preferences')->insert(['user_id' => 1, 'category_id' => $cat1->id]);

        $response = $this->postJson('/api/v1/user/preferences/categories', [
            'user_id'    => 1,
            'categories' => [$cat2->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.saved', 1);

        $this->assertDatabaseMissing('user_category_preferences', ['user_id' => 1, 'category_id' => $cat1->id]);
        $this->assertDatabaseHas('user_category_preferences', ['user_id' => 1, 'category_id' => $cat2->id]);
    }
}
