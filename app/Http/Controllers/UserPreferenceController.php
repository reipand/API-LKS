<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPreferenceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $userId      = $request->input('user_id');
        $categoryIds = $request->input('categories', []);

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'user_id is required'], 422);
        }

        if (!is_array($categoryIds) || empty($categoryIds)) {
            return response()->json(['success' => false, 'message' => 'categories must be a non-empty array'], 422);
        }

        $validIds = DB::table('categories')->whereIn('id', $categoryIds)->pluck('id')->toArray();

        DB::table('user_category_preferences')->where('user_id', $userId)->delete();

        $rows = array_map(fn($id) => ['user_id' => $userId, 'category_id' => $id], $validIds);

        if (!empty($rows)) {
            DB::table('user_category_preferences')->insert($rows);
        }

        return response()->json(['success' => true, 'message' => 'Preferences saved', 'data' => ['saved' => count($rows)]]);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'user_id is required', 'data' => []], 422);
        }

        $preferences = DB::table('user_category_preferences')
            ->join('categories', 'user_category_preferences.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name', 'categories.slug')
            ->where('user_category_preferences.user_id', $userId)
            ->orderBy('categories.name')
            ->get();

        return response()->json(['success' => true, 'message' => 'Success', 'data' => $preferences]);
    }
}
