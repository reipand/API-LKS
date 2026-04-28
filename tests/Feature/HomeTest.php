<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    // ─── breaking ─────────────────────────────────────────────────────────────

    public function test_breaking_returns_latest_published_articles(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(3)->create(['category_id' => $category->id]);
        Article::factory()->unpublished()->count(2)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/home/breaking');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'image_url',
                                                'category_name', 'published_at']]]);
    }

    public function test_breaking_returns_articles_sorted_by_latest(): void
    {
        $category = Category::factory()->create();
        Article::factory()->create([
            'category_id'  => $category->id,
            'title'        => 'Old Article',
            'published_at' => now()->subDays(5),
        ]);
        Article::factory()->create([
            'category_id'  => $category->id,
            'title'        => 'New Article',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/home/breaking');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'New Article');
    }

    public function test_breaking_defaults_to_5_articles(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(10)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/home/breaking');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_breaking_respects_limit_param(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(10)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/home/breaking?limit=3');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_breaking_caps_limit_at_20(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(25)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/home/breaking?limit=100');

        $response->assertOk()
            ->assertJsonCount(20, 'data');
    }

    // ─── recommendation ───────────────────────────────────────────────────────

    public function test_recommendation_requires_user_id(): void
    {
        $response = $this->getJson('/api/v1/home/recommendation');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_recommendation_returns_latest_when_no_preferences(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/home/recommendation?user_id=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data');
    }

    public function test_recommendation_returns_articles_from_preferred_categories(): void
    {
        $preferred  = Category::factory()->create();
        $other      = Category::factory()->create();

        Article::factory()->count(3)->create(['category_id' => $preferred->id]);
        Article::factory()->count(4)->create(['category_id' => $other->id]);

        DB::table('user_category_preferences')->insert([
            ['user_id' => 1, 'category_id' => $preferred->id],
        ]);

        $response = $this->getJson('/api/v1/home/recommendation?user_id=1');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_recommendation_excludes_bookmarked_articles(): void
    {
        $category = Category::factory()->create();
        $bookmarked = Article::factory()->create(['category_id' => $category->id]);
        Article::factory()->count(2)->create(['category_id' => $category->id]);

        DB::table('user_category_preferences')->insert(['user_id' => 1, 'category_id' => $category->id]);
        DB::table('bookmarks')->insert([
            'user_id' => 1, 'article_id' => $bookmarked->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/home/recommendation?user_id=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($bookmarked->id, $ids->toArray());
    }

    public function test_recommendation_respects_limit_param(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(20)->create(['category_id' => $category->id]);
        DB::table('user_category_preferences')->insert(['user_id' => 1, 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/home/recommendation?user_id=1&limit=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }
}
