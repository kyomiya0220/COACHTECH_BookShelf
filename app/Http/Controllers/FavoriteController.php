<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index()
    {
        // ログインユーザーのお気に入り書籍を投稿日降順などで取得（ページネーション付き）
        $books = auth()->user()->favoriteBooks()
            ->with(['genres'])
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }
    public function toggle(Book $book)
    {
        // 未ログイン時はメッセージ付きでログイン画面へ
        if (auth()->guest()) {
            return redirect()
                ->route('login')
                ->with('message', 'この機能を利用するにはログインが必要です。');
        }

        // トグル処理（登録済みなら解除、未登録なら追加）
        auth()->user()->favoriteBooks()->toggle($book->id);

        return redirect()->back();
    }

    public function store(Request $request, Book $book)
    {
        if (auth()->guest()) {
            return redirect()
                ->route('login')
                ->with('message', 'この機能を利用するにはログインが必要です。');
        }

        auth()->user()->favoriteBooks()->syncWithoutDetaching([$book->id]);

        return back()->with('success', 'お気に入りに追加しました。');
    }

    public function destroy(Book $book)
    {
        if (auth()->guest()) {
            return redirect()
                ->route('login')
                ->with('message', 'この機能を利用するにはログインが必要です。');
        }

        auth()->user()->favoriteBooks()->detach($book->id);

        return back()->with('success', 'お気に入りを解除しました。');
    }
}