<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;

// ゲスト専用（ログイン済みは /books へリダイレクト）
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware(['throttle:custom-limit']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware(['throttle:custom-limit']);
});

// トップページ（/）アクセス時も一覧を表示
Route::get('/', [BookController::class, 'index']);

// 書籍のCRUD（index, show, create, store, edit, update, destroy）を一括定義
Route::resource('books', BookController::class);

// ログイン必須のその他ルート
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/ranking', fn() => 'ランキング（準備中）')->name('ranking.index');
    Route::get('/favorites', fn() => 'お気に入り（準備中）')->name('favorites.index');
    Route::get('/genres', fn() => 'ジャンル管理（準備中）')->name('genres.index');
});

// 関連機能（お気に入り・レビュー）
Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::post('/reviews/{review}/like', [ReviewController::class, 'like'])->name('reviews.like');