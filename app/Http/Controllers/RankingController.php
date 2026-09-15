<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function index()
    {
        $rankedBooks = Book::has('reviews') // レビューが存在する書籍のみ
            ->withAvg('reviews', 'rating')  // reviews_avg_rating を追加
            ->withCount('reviews')          // reviews_count を追加
            ->orderByDesc('reviews_avg_rating')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
