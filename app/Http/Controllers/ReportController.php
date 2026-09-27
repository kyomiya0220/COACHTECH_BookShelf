<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Review;

class ReportController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // ログインユーザーの全レビューを取得
        $userReviews = Review::where('user_id', $userId);

        // 1. 基本サマリー
        $totalReviews = (clone $userReviews)->count();
        $booksRead = (clone $userReviews)->distinct('book_id')->count('book_id');
        $averageRating = (clone $userReviews)->avg('rating') ?: 0;

        // 2. 評価分布（1〜5星ごとの件数）
        // Collection のキーを 0〜4 (1〜5星に対応) で初期化
        $ratingDistribution = collect([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]);
        $counts = (clone $userReviews)
            ->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $ratingDistribution = $ratingDistribution->merge($counts)->values();

        // 3. 高評価書籍 TOP5（★4以上、評価の降順、最大5件）
        $topRatedBooks = (clone $userReviews)
            ->with('book')
            ->where('rating', '>=', 4)
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->book_id,
                    'title' => $review->book->title ?? 'タイトル不明',
                    'author' => $review->book->author ?? '著者不明',
                    'rating' => $review->rating,
                ];
            });

        $userReviews = Review::where('reviews.user_id', auth()->id());

        // 4. ジャンル別評価傾向 TOP5（平均評価の降順、最大5件）
        $genreRatings = (clone $userReviews)
            ->join('books', 'reviews.book_id', '=', 'books.id')
            ->join('genres', 'books.genre_id', '=', 'genres.id')
            ->select(
                'genres.id',
                'genres.name',
                DB::raw('AVG(reviews.rating) as average_rating'),
                DB::raw('COUNT(reviews.id) as count')
            )
            ->groupBy('genres.id', 'genres.name')
            ->orderBy('average_rating', 'desc')
            ->take(5)
            ->get()
            ->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                    'average_rating' => (float) $genre->average_rating,
                    'count' => $genre->count,
                ];
            });

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
