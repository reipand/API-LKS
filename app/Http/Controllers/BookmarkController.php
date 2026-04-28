<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookmarkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'user_id is required', 'data' => []], 422);
        }

        $bookmarks = DB::table('bookmarks')
            ->join('articles', 'bookmarks.article_id', '=', 'articles.id')
            ->join('categories', 'articles.category_id', '=', 'categories.id')
            ->select(
                'articles.id',
                'articles.title',
                'articles.slug',
                'articles.excerpt',
                'articles.image_url',
                'categories.name as category_name',
                'articles.author',
                DB::raw('DATE(articles.published_at) as published_at')
            )
            ->where('bookmarks.user_id', $userId)
            ->orderByDesc('bookmarks.created_at')
            ->get();

        return response()->json(['success' => true, 'message' => 'Success', 'data' => $bookmarks]);
    }

    public function store(Request $request): JsonResponse
    {
        $userId    = $request->input('user_id');
        $articleId = $request->input('article_id');

        if (!$userId || !$articleId) {
            return response()->json(['success' => false, 'message' => 'user_id and article_id are required'], 422);
        }

        if (!DB::table('articles')->where('id', $articleId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Article not found'], 404);
        }

        if (DB::table('bookmarks')->where('user_id', $userId)->where('article_id', $articleId)->exists()) {
            return response()->json(['success' => false, 'message' => 'Article already bookmarked'], 409);
        }

        DB::table('bookmarks')->insert([
            'user_id'    => $userId,
            'article_id' => $articleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Bookmark added'], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $userId    = $request->input('user_id');
        $articleId = $request->input('article_id');

        if (!$userId || !$articleId) {
            return response()->json(['success' => false, 'message' => 'user_id and article_id are required'], 422);
        }

        $deleted = DB::table('bookmarks')
            ->where('user_id', $userId)
            ->where('article_id', $articleId)
            ->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Bookmark not found'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Bookmark removed']);
    }
}
