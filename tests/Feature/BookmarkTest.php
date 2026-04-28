<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(): Article
    {
        return Article::factory()->create(['category_id' => Category::factory()->create()->id]);
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_index_requires_user_id(): void
    {
        $response = $this->getJson('/api/v1/bookmarks');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_index_returns_user_bookmarks(): void
    {
        $article1 = $this->makeArticle();
        $article2 = $this->makeArticle();
        DB::table('bookmarks')->insert([
            ['user_id' => 1, 'article_id' => $article1->id, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 1, 'article_id' => $article2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson('/api/v1/bookmarks?user_id=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'slug', 'excerpt',
                                                'image_url', 'category_name', 'author', 'published_at']]]);
    }

    public function test_index_returns_empty_for_user_with_no_bookmarks(): void
    {
        $response = $this->getJson('/api/v1/bookmarks?user_id=99');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_index_only_returns_bookmarks_for_requested_user(): void
    {
        $article = $this->makeArticle();
        DB::table('bookmarks')->insert([
            'user_id' => 1, 'article_id' => $article->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/bookmarks?user_id=2');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_store_requires_user_id_and_article_id(): void
    {
        $response = $this->postJson('/api/v1/bookmarks', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_requires_article_id(): void
    {
        $response = $this->postJson('/api/v1/bookmarks', ['user_id' => 1]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_adds_bookmark(): void
    {
        $article = $this->makeArticle();

        $response = $this->postJson('/api/v1/bookmarks', [
            'user_id'    => 1,
            'article_id' => $article->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('bookmarks', ['user_id' => 1, 'article_id' => $article->id]);
    }

    public function test_store_returns_409_for_duplicate_bookmark(): void
    {
        $article = $this->makeArticle();
        DB::table('bookmarks')->insert([
            'user_id' => 1, 'article_id' => $article->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/bookmarks', [
            'user_id'    => 1,
            'article_id' => $article->id,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_store_returns_404_for_nonexistent_article(): void
    {
        $response = $this->postJson('/api/v1/bookmarks', [
            'user_id'    => 1,
            'article_id' => 9999,
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_destroy_requires_user_id_and_article_id(): void
    {
        $response = $this->deleteJson('/api/v1/bookmarks', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_destroy_removes_bookmark(): void
    {
        $article = $this->makeArticle();
        DB::table('bookmarks')->insert([
            'user_id' => 1, 'article_id' => $article->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->deleteJson('/api/v1/bookmarks', [
            'user_id'    => 1,
            'article_id' => $article->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('bookmarks', ['user_id' => 1, 'article_id' => $article->id]);
    }

    public function test_destroy_returns_404_when_bookmark_not_found(): void
    {
        $article = $this->makeArticle();

        $response = $this->deleteJson('/api/v1/bookmarks', [
            'user_id'    => 1,
            'article_id' => $article->id,
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
