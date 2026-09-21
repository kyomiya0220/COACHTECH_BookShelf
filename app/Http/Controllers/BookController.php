<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Requests\BookRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct()
    {
        // create（画面表示）と store（保存処理）のアクションだけログイン必須にする
        $this->middleware('auth')->only(['create', 'store']);
    }

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

    public function store(StoreBookRequest $request)
    {
        // 1. バリデーション済みデータを取得
        $validated = $request->validated();

        // 2. ログイン中のユーザーIDと代表ジャンルIDをセット
        $validated['user_id'] = auth()->id();

        // DBのgenre_idカラム（必須）を埋めるための処理
        if (isset($validated['genres']) && is_array($validated['genres']) && count($validated['genres']) > 0) {
            $validated['genre_id'] = $validated['genres'][0];
        }

        // 3. 書籍本体を保存
        $book = Book::create($validated);

        // 4. ジャンル（多対多リレーション）を保存する処理
        if (isset($validated['genres'])) {
            $book->genres()->sync($validated['genres']);
        }

        return redirect()->route('books.index');
    }

    public function show(Book $book)
    {
        // リレーション（ジャンル、レビューとその投稿者、いいね数）をロード
        $book->load(['genres', 'reviews.user'])
            ->loadCount('favorites');

        return view('books.show', compact('book'));
    }

    public function edit(Book $book)
    {
        if (Auth::guest() || Auth::id() !== $book->user_id) {
            abort(403, 'この書籍を編集する権限がありません。');
        }

        // 全ジャンルを取得
        $genres = Genre::all();

        // 該当の書籍に登録されているジャンルIDの配列を取得
        $bookGenreIds = $book->genres->pluck('id')->toArray();

        return view('books.edit', compact('book', 'genres', 'bookGenreIds'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        // 書籍本体の更新
        $book->update($validated);

        if (isset($validated['genres'])) {
            $book->genres()->sync($validated['genres']);
        } else {
            $book->genres()->detach();
        }

        return redirect()->route('books.show', $book);
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        // 書籍データを削除
        $book->delete();

        // 削除完了後、一覧画面へリダイレクトしてメッセージを表示
        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}