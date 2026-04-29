<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function breaking(Request $request): JsonResponse
    {
        $limit = min((int) $request->query('limit', 5), 20);

        $articles = DB::table('articles')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->select(
                'articles.id',
                'articles.title',
                'articles.slug',
                'articles.image_url',
                'categories.name as category_name',
                DB::raw("DATE(articles.published_at) as published_at")
            )
            ->whereNotNull('articles.published_at')
            ->orderByDesc('articles.published_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $articles,
        ]);
    }

    public function recommendation(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $limit  = min((int) $request->query('limit', 10), 50);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is required',
                'data' => [],
            ], 422);
        }

        $categoryIds = DB::table('user_category_preferences')
            ->where('user_id', $userId)
            ->pluck('category_id');

        if ($categoryIds->isEmpty()) {
            $articles = DB::table('articles')
                ->join('categories', 'articles.category_id', '=', 'categories.id')
                ->select(
                    'articles.id',
                    'articles.title',
                    'articles.slug',
                    'articles.excerpt',
                    'articles.image_url',
                    'categories.name as category_name',
                    'articles.author',
                    DB::raw("DATE(articles.published_at) as published_at")
                )
                ->whereNotNull('articles.published_at')
                ->orderByDesc('articles.published_at')
                ->limit($limit)
                ->get();
        } else {
            $bookmarkedIds = DB::table('bookmarks')
                ->where('user_id', $userId)
                ->pluck('article_id');

            $articles = DB::table('articles')
                ->join('categories', 'articles.category_id', '=', 'categories.id')
                ->select(
                    'articles.id',
                    'articles.title',
                    'articles.slug',
                    'articles.excerpt',
                    'articles.image_url',
                    'categories.name as category_name',
                    'articles.author',
                    DB::raw("DATE(articles.published_at) as published_at")
                )
                ->whereIn('articles.category_id', $categoryIds)
                ->when($bookmarkedIds->isNotEmpty(), fn($q) => $q->whereNotIn('articles.id', $bookmarkedIds))
                ->whereNotNull('articles.published_at')
                ->distinct()
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $articles,
        ]);
    }
}
