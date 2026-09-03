<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookController;

// ゲスト専用（ログイン済みの場合は /books にリダイレクト）
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware(['throttle:custom-limit']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware(['throttle:custom-limit']);
});

// ゲスト・ログインユーザー共にアクセス可能
Route::get('/', [BookController::class, 'index'])->name('books.index');
Route::get('/books', [BookController::class, 'index']);

// ログインユーザー専用（未認証なら自動的に /login へリダイレクト）
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');

    Route::get('/ranking', fn() => 'ランキング（準備中）')->name('ranking.index');
    Route::get('/favorites', fn() => 'お気に入り（準備中）')->name('favorites.index');
    Route::get('/genres', fn() => 'ジャンル管理（準備中）')->name('genres.index');
});

// ワイルドカード({book})を含むルーティングは一番下
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');