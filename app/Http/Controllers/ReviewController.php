<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Book $book)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // データベースに保存
        Review::create([
            'user_id' => auth()->id(),
            'book_id' => $book->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->back()->with('success', 'レビューを投稿しました。');
    }

    public function edit(Review $review)
    {
        // 自身の投稿でない場合は403エラーにする場合（Policyを使っていない場合）
        if ($review->user_id !== auth()->id()) {
            abort(403);
        }

        return view('reviews.edit', compact('review'));
    }

    // レビューの更新
    public function update(StoreReviewRequest $request, Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            abort(403);
        }

        $review->update($request->validated());

        return redirect()->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    // レビューの削除
    public function destroy(Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            abort(403);
        }

        $review->delete();

        return redirect()->back()
            ->with('success', 'レビューを削除しました。');
    }
    public function like(Review $review)
    {
        $user = auth()->user();

        // 既にいいねしている場合は解除、していなければ追加
        if ($user->likedReviews->contains($review->id)) {
            $user->likedReviews()->detach($review->id);
        } else {
            $user->likedReviews()->attach($review->id);
        }

        return back();
    }
}
