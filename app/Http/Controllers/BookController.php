<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index()
    {
        // ビューで使われている平均評価(reviews_avg_rating)やジャンル(genres)、ページネーション(links)に対応する取得処理
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->paginate(9); // または get() や paginate()

        return view('books.index', compact('books'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function show(Book $book)
    {
        return view('books.show', compact('book'));
    }
}