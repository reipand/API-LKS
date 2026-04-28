<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = DB::table('categories')
            ->select('id', 'name', 'slug')
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'message' => 'Success', 'data' => $categories]);
    }
}
