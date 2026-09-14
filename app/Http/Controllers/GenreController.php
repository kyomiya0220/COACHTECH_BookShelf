<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    // ジャンル一覧（書籍数付き）
    public function index()
    {
        $genres = Genre::withCount('books')->get();
        return view('genres.index', compact('genres'));
    }

    // ジャンル詳細（紐づく書籍を10件ずつページネーション）
    public function show(Genre $genre)
    {
        $books = $genre->books()->paginate(10);
        return view('genres.show', compact('genre', 'books'));
    }

    // 登録フォーム表示
    public function create()
    {
        return view('genres.create');
    }

    // 登録処理
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
        ]);

        Genre::create($request->only('name'));

        return redirect()->route('genres.index')->with('success', 'ジャンルを作成しました。');
    }

    // 編集フォーム表示
    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    // 更新処理
    public function update(Request $request, Genre $genre)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
        ]);

        $genre->update($request->only('name'));

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    // 削除処理（書籍が紐付いている場合は制限）
    public function destroy(Genre $genre)
    {
        if ($genre->books()->exists()) {
            return back()->with('error', '書籍が紐付いているジャンルは削除できません。');
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}