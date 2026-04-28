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

        $preferredIds = DB::table('user_category_preferences')
            ->where('user_id', $userId)
            ->pluck('category_id')
            ->toArray();

        $categories = DB::table('categories')
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get()
            ->map(fn($cat) => (object) [
                'id'           => $cat->id,
                'name'         => $cat->name,
                'slug'         => $cat->slug,
                'is_preferred' => in_array($cat->id, $preferredIds),
            ]);

        return response()->json(['success' => true, 'message' => 'Success', 'data' => $categories]);
    }
}
