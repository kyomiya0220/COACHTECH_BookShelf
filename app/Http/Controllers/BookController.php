<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        // 10件/ページでペジネーション（ジャンルリレーションを事前ロード）
        $books = Book::with('genres')->latest()->paginate(10);

        return view('books.index', compact('books'));
    }

    public function create()
    {
        // 全ジャンルを取得
        $genres = Genre::all();

        // 新規作成時は選択中のジャンルIDは空配列
        $bookGenreIds = [];

        return view('books.create', compact('genres', 'bookGenreIds'));
    }

    public function show(Book $book)
    {
        // リレーション（ジャンル、レビューとその投稿者、いいね数）をロード
        $book->load(['genres', 'reviews.user'])
            ->loadCount('favorites');

        return view('books.show', compact('book'));
    }
}