<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookController;



// 会員登録画面
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// ログイン画面
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);


Route::middleware(['auth'])->group(function () {

    // ログアウトルート
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // 書籍関連のルート
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/ranking', fn() => 'ランキング（準備中）')->name('ranking.index');
    Route::get('/favorites', fn() => 'お気に入り（準備中）')->name('favorites.index');
    Route::get('/genres', fn() => 'ジャンル管理（準備中）')->name('genres.index');

});