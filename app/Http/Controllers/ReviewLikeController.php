<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    public function store(Request $request, Review $review)
    {
        // 未ログイン時はメッセージ付きでログイン画面へ
        if (auth()->guest()) {
            return redirect()
                ->route('login')
                ->with('message', 'この機能を利用するにはログインが必要です。');
        }

        // いいね追加処理（多対多またはトグル処理）
        auth()->user()->likes()->syncWithoutDetaching([$review->id]);

        return back();
    }
}