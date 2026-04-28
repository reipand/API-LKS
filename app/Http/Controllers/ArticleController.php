<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $page     = max(1, (int) $request->query('page', 1));
        $limit    = min((int) $request->query('limit', 10), 50);
        $search   = trim($request->query('search', ''));
        $category = trim($request->query('category', ''));
        $offset   = ($page - 1) * $limit;

        $query = DB::table('articles')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->select(
                'articles.id',
                'articles.title',
                'articles.slug',
                'articles.excerpt',
                'articles.image_url',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'articles.author',
                'articles.views',
                DB::raw("DATE(articles.published_at) as published_at")
            )
            ->whereNotNull('articles.published_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('articles.title', 'like', "%{$search}%")
                  ->orWhere('articles.excerpt', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('categories.slug', $category);
        }

        $total   = $query->count();
        $articles = $query->orderByDesc('articles.published_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'meta' => [
                'page'     => $page,
                'limit'    => $limit,
                'total'    => $total,
                'has_more' => ($offset + $limit) < $total,
            ],
            'data' => $articles,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $article = DB::table('articles')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->select(
                'articles.id',
                'articles.title',
                'articles.slug',
                'articles.excerpt',
                'articles.content',
                'articles.image_url',
                'categories.name as category',
                'articles.author',
                'articles.views',
                DB::raw("DATE(articles.published_at) as published_at")
            )
            ->where('articles.slug', $slug)
            ->first();

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
                'data' => null,
            ], 404);
        }

        $tags = DB::table('tags')
            ->join('article_tags', 'tags.id', '=', 'article_tags.tag_id')
            ->where('article_tags.article_id', $article->id)
            ->pluck('tags.name');

        $result = (array) $article;
        $result['tags'] = $tags;

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $result,
        ]);
    }

    public function related(Request $request, int $id): JsonResponse
    {
        $limit = min((int) $request->query('limit', 3), 10);

        $article = DB::table('articles')->where('id', $id)->select('category_id')->first();

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
                'data' => [],
            ], 404);
        }

        $related = DB::table('articles')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->select(
                'articles.id',
                'articles.title',
                'articles.slug',
                'articles.excerpt',
                'articles.image_url',
                'categories.name as category_name',
                DB::raw("DATE(articles.published_at) as published_at")
            )
            ->where('articles.category_id', $article->category_id)
            ->where('articles.id', '!=', $id)
            ->whereNotNull('articles.published_at')
            ->orderByDesc('articles.published_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $related,
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 10), 50);

        $articles = DB::table('articles')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->select(
                'articles.id',
                'articles.title',
                'articles.slug',
                'articles.excerpt',
                'articles.image_url',
                'categories.name as category_name',
                'articles.views',
                DB::raw("DATE(articles.published_at) as published_at")
            )
            ->whereNotNull('articles.published_at')
            ->orderByDesc('articles.views')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $articles,
        ]);
    }

    public function incrementView(int $id): JsonResponse
    {
        $updated = DB::table('articles')->where('id', $id)->increment('views');

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Article not found',
            ], 404);
        }

        $views = DB::table('articles')->where('id', $id)->value('views');

        return response()->json([
            'success' => true,
            'message' => 'View recorded',
            'data' => ['views' => $views],
        ]);
    }
}
