<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    /**
     * AP01: 書籍一覧取得
     */
    public function index(Request $request)
    {
        $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

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
            // ID指定で検索（リレーションと集計も含める）
            $book = Book::with(['genres', 'reviews.user'])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->findOrFail($id);

            return response()->json([
                'data' => new BookResource($book)
            ], 200);

        } catch (ModelNotFoundException $e) {
            // 404エラー用JSONレスポンス
            return response()->json([
                'error' => '書籍が見つかりませんでした。'
            ], 404);
        }
    }

    /**
     * AP03: 書籍新規登録
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'regex:/^\d{13}$/', 'unique:books,isbn'],
            'published_date' => ['required', 'date', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:1000'],
            'genre_id' => ['required', 'integer', 'exists:genres,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $userId = auth()->id() ?? $validated['user_id'] ?? \App\Models\User::first()?->id ?? 1;

        $book = Book::create([
            'user_id' => $userId,
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
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'author' => ['sometimes', 'required', 'string', 'max:255'],
            'isbn' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\d{13}$/',
                Rule::unique('books', 'isbn')->ignore($book->id),
            ],
            'published_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:1000'],
            'genre_id' => ['sometimes', 'required', 'integer', 'exists:genres,id'],
        ]);

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
        $book->delete();

        return response()->noContent();
    }
}