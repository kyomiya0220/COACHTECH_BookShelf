<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReadingPlanController;

// ゲスト専用ルート
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware(['throttle:custom-limit']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware(['throttle:custom-limit']);
});

// トップページ（/）アクセス時も一覧を表示
Route::get('/', [BookController::class, 'index']);

// ランキング
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

// 書籍（/books）関連のルート（一覧・詳細は未ログインOK、その他は Controller 側で制御）
Route::resource('books', BookController::class);


// ログイン必須（auth）のルートグループ
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ジャンル管理
    Route::resource('genres', GenreController::class);

    // マイ読書レポート
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // 読書計画ルート
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])->name('reading-plans.index');

    // 読書計画のCRUD一括指定 (index, create, store, edit, update, destroy)
    Route::resource('reading-plans', ReadingPlanController::class);

    // 「読了する」処理用のアクションルート
    Route::post('/reading-plans/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');


    // 通知ルート
    Route::get('/notifications', fn() => redirect()->route('books.index'))->name('notifications.index');

    // ISBN検索API
    Route::get('/books/isbn/{isbn}', [BookController::class, 'fetchByIsbn'])->name('books.fetchByIsbn');

    // お気に入り関連
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // レビュー関連
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/like', [ReviewController::class, 'like'])->name('reviews.like');
});