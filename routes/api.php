<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Home
    Route::get('/home/breaking', [HomeController::class, 'breaking']);
    Route::get('/home/recommendation', [HomeController::class, 'recommendation']);

    // Articles
    Route::get('/articles/trending', [ArticleController::class, 'trending']);
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{slug}', [ArticleController::class, 'show'])->where('slug', '[a-z0-9\-]+');
    Route::get('/articles/{id}/related', [ArticleController::class, 'related'])->where('id', '[0-9]+');
    Route::post('/articles/{id}/view', [ArticleController::class, 'incrementView'])->where('id', '[0-9]+')->middleware('throttle:article-view');

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);

    // Bookmarks
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/bookmarks', [BookmarkController::class, 'store']);
    Route::delete('/bookmarks', [BookmarkController::class, 'destroy']);

    // User preferences
    Route::get('/user/preferences/categories', [UserPreferenceController::class, 'index']);
    Route::post('/user/preferences/categories', [UserPreferenceController::class, 'store']);
});
