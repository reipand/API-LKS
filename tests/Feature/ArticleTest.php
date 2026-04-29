<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_articles(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message',
                'meta' => ['page', 'limit', 'total', 'has_more'],
                'data' => [['id', 'title', 'slug', 'excerpt', 'image_url',
                             'category_name', 'category_slug', 'author', 'views', 'published_at']],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.page', 1);
    }

    public function test_index_excludes_unpublished_articles(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(2)->create(['category_id' => $category->id]);
        Article::factory()->unpublished()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_index_filters_by_search_title(): void
    {
        $category = Category::factory()->create();
        Article::factory()->create(['title' => 'Laravel is Amazing', 'category_id' => $category->id]);
        Article::factory()->create(['title' => 'Vue.js Basics', 'category_id' => $category->id]);

        $response = $this->getJson('/api/v1/articles?search=Laravel');

        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Laravel is Amazing');
    }

    public function test_index_filters_by_category_slug(): void
    {
        $tech    = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $sports  = Category::factory()->create(['name' => 'Sports', 'slug' => 'sports']);
        Article::factory()->count(2)->create(['category_id' => $tech->id]);
        Article::factory()->count(3)->create(['category_id' => $sports->id]);

        $response = $this->getJson('/api/v1/articles?category=technology');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_index_respects_limit_param(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(20)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/articles?limit=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.limit', 5)
            ->assertJsonPath('meta.has_more', true);
    }

    public function test_index_caps_limit_at_50(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(60)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/articles?limit=100');

        $response->assertOk()
            ->assertJsonPath('meta.limit', 50);
    }

    public function test_index_respects_page_param(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(15)->create(['category_id' => $category->id]);

        $page1 = $this->getJson('/api/v1/articles?limit=10&page=1');
        $page2 = $this->getJson('/api/v1/articles?limit=10&page=2');

        $page1->assertOk()->assertJsonCount(10, 'data');
        $page2->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_index_returns_empty_data_when_no_articles(): void
    {
        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);
    }

    // ─── show ─────────────────────────────────────────────────────────────────

    public function test_show_returns_article_with_tags(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id]);
        $tag      = Tag::factory()->create();
        DB::table('article_tags')->insert(['article_id' => $article->id, 'tag_id' => $tag->id]);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $article->slug)
            ->assertJsonStructure(['data' => ['id', 'title', 'slug', 'excerpt', 'content',
                                               'image_url', 'category', 'author', 'views',
                                               'published_at', 'tags']])
            ->assertJsonCount(1, 'data.tags');
    }

    public function test_show_returns_404_for_nonexistent_slug(): void
    {
        $response = $this->getJson('/api/v1/articles/slug-yang-tidak-ada');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_show_returns_article_with_empty_tags_array(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/articles/{$article->slug}");

        $response->assertOk()
            ->assertJsonPath('data.tags', []);
    }

    // ─── trending ─────────────────────────────────────────────────────────────

    public function test_trending_returns_articles_sorted_by_views(): void
    {
        $category = Category::factory()->create();
        Article::factory()->create(['category_id' => $category->id, 'views' => 100]);
        Article::factory()->create(['category_id' => $category->id, 'views' => 5000]);
        Article::factory()->create(['category_id' => $category->id, 'views' => 300]);

        $response = $this->getJson('/api/v1/articles/trending');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.views', 5000);
    }

    public function test_trending_caps_limit_at_50(): void
    {
        $category = Category::factory()->create();
        Article::factory()->count(60)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/articles/trending?limit=100');

        $response->assertOk()
            ->assertJsonCount(50, 'data');
    }

    public function test_trending_excludes_unpublished(): void
    {
        $category = Category::factory()->create();
        Article::factory()->create(['category_id' => $category->id, 'views' => 9999]);
        Article::factory()->unpublished()->create(['category_id' => $category->id, 'views' => 99999]);

        $response = $this->getJson('/api/v1/articles/trending');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ─── related ──────────────────────────────────────────────────────────────

    public function test_related_returns_same_category_articles(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id]);
        Article::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/articles/{$article->id}/related");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_related_excludes_the_article_itself(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/articles/{$article->id}/related");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_related_returns_404_for_nonexistent_article(): void
    {
        $response = $this->getJson('/api/v1/articles/9999/related');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_related_respects_limit_param(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id]);
        Article::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/articles/{$article->id}/related?limit=2");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ─── incrementView ────────────────────────────────────────────────────────

    public function test_increment_view_increases_count(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id, 'views' => 10]);

        $response = $this->postJson("/api/v1/articles/{$article->id}/view");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.views', 11);

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'views' => 11]);
    }

    public function test_increment_view_returns_404_for_nonexistent_article(): void
    {
        $response = $this->postJson('/api/v1/articles/9999/view');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_increment_view_allows_repeated_requests(): void
    {
        $category = Category::factory()->create();
        $article  = Article::factory()->create(['category_id' => $category->id, 'views' => 0]);

        for ($i = 1; $i <= 20; $i++) {
            $this->postJson("/api/v1/articles/{$article->id}/view")->assertOk();
        }

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'views' => 20]);
    }
}
