<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * AP01: 書籍一覧取得
     */
    public function index(IndexBookRequest $request)
    {
        // バリデーションは IndexBookRequest で自動処理されます

        $query = Book::with(['genres'])->withAvg('reviews', 'rating')->withCount('reviews');

        // キーワード検索 (タイトル・著者)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // ジャンル絞り込み
        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre_id);
            });
        }

        $perPage = $request->input('per_page', 20);
        $books = $query->latest()->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * AP02: 書籍詳細取得
     */
    public function show(string $id): JsonResponse
    {
        try {
            $book = Book::with(['genres', 'reviews.user'])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->findOrFail($id);

            return response()->json([
                'data' => new BookResource($book),
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }
    }

    /**
     * AP03: 書籍新規登録
     */
    public function store(StoreBookRequest $request)
    {
        // バリデーション済みデータを取得
        $validated = $request->validated();

        // ログインユーザーに紐づけて作成
        $book = $request->user()->books()->create([
            'genre_id' => $validated['genre_id'],
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['genre_id'])) {
            $book->genres()->attach($validated['genre_id']);
        }

        $book->load(['genres']);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * AP04: 書籍情報更新
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        // 1. 認可チェック（所有者でなければここで 403 エラーを返却）
        $this->authorize('update', $book);

        // バリデーション済みデータを取得
        $validated = $request->validated();

        $book->update($request->only(['title', 'author', 'isbn', 'published_date', 'description']));

        if ($request->has('genre_id')) {
            $book->genres()->sync([$request->genre_id]);
        }

        $book->load(['genres']);

        return new BookResource($book);
    }

    /**
     * AP05: 書籍削除
     */
    public function destroy(Book $book)
    {
        // 1. 認可チェック（所有者でなければここで 403 エラーを返却）
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
