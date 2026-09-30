<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    // 一覧表示 & ステータス絞り込み
    public function index(Request $request)
    {
        $currentStatus = $request->input('status');

        // ログインユーザー自身の読書計画のみを取得（ここで他人のデータが入るのを防ぐ！）
        $query = auth()->user()->readingPlans()->with('book');

        if ($currentStatus) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query->latest()->paginate(10);

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    // 新規作成画面
    public function create()
    {
        $books = Book::all(); // フォームで選択する書籍リスト

        return view('reading-plans.create', compact('books'));
    }

    // 登録処理
    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();

        auth()->user()->readingPlans()->create([
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Reading->value, // 初期状態（例: 読書中）
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    // 編集画面（Policy 認可あり）
    public function edit(ReadingPlan $readingPlan)
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    // 更新処理（Policy 認可あり）
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan)
    {
        // バリデーション済みのデータのみ取得
        $validated = $request->validated();

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    // 削除処理（Policy 認可あり）
    public function destroy(ReadingPlan $readingPlan)
    {
        // ★ ログインユーザー以外のデータであれば 403 エラーを返す
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403, '他人の読書計画は削除できません。');
        }

        // $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    // 「読了する」処理（Policy 認可あり）
    public function complete(ReadingPlan $readingPlan)
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を完了しました。');
    }
}
