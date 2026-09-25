<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct()
    {
        // create（画面表示）と store（保存処理）のアクションだけログイン必須にする
        $this->middleware('auth')->only(['create', 'store']);
    }

    /**
     * 書籍一覧表示（検索・絞り込み・ソート対応）
     */
    public function index(Request $request): View
    {
        // 1. クエリビルダの初期化（ジャンルリレーション・レビュー集計の Eager Loading）
        $query = Book::query()
            ->with(['genres', 'reviews'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        // 2. キーワード検索（タイトル または 著者名：前後の半角・全角スペースをトリム）
        if ($request->filled('keyword')) {
            // 全角スペース（\u{3000}）および半角スペースのトリム処理
            $keywordInput = preg_replace('/^[\s\x{3000}]+|[\s\x{3000}]+$/u', '', $request->input('keyword'));

            if ($keywordInput !== '') {
                $keyword = '%' . addcslashes($keywordInput, '%_\\') . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', $keyword)
                        ->orWhere('author', 'like', $keyword);
                });
            }
        }

        // 3. ジャンル絞り込み（GETパラメータ: genre）
        if ($request->filled('genre')) {
            $genreId = $request->input('genre');
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        // 4. ソート順制御（不正なパラメータの場合は fallback として newest に処理）
        $sort = $request->input('sort', 'newest');

        switch ($sort) {
            case 'oldest':
                // 登録日が古い順
                $query->orderBy('id', 'asc');
                break;

            case 'title':
                // タイトル昇順
                $query->orderBy('title', 'asc');
                break;

            case 'rating':
                // 平均評価が高い順（レビューなし/NULLは最後、同率は登録順 desc）
                $query->orderByRaw('reviews_avg_rating IS NULL ASC')
                    ->orderBy('reviews_avg_rating', 'desc')
                    ->orderBy('id', 'desc');
                break;

            case 'newest':
            default:
                // 登録日が新しい順（デフォルト）
                $query->orderBy('id', 'desc');
                break;
        }

        // 5. ページネーション（1ページ10件）＆ 検索クエリ保持
        $books = $query->paginate(10)->withQueryString();

        // 検索フォーム用のジャンル一覧も取得して View へ渡す
        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    public function create()
    {
        // 全ジャンルを取得
        $genres = Genre::all();

        // 新規作成時は選択中のジャンルIDは空配列
        $bookGenreIds = [];

        return view('books.create', compact('genres', 'bookGenreIds'));
    }

    /**
     * ISBNからGoogle Books API経由で書籍情報を取得する（提供Blade JS仕様準拠）
     */
    public function fetchByIsbn(string $isbn): JsonResponse
    {
        // a. 13桁チェック（数値かつ13桁）
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。'
            ], 400);
        }

        try {
            $apiKey = config('services.google_books.api_key') ?: env('GOOGLE_BOOKS_API_KEY');
            $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}";

            if ($apiKey) {
                $url .= "&key={$apiKey}";
            }

            $response = Http::get($url);

            // c. クォータ超過チェック (429 Too Many Requests)
            if ($response->status() === 429) {
                return response()->json([
                    'error' => 'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。'
                ], 429);
            }

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'API通信エラーが発生しました。'
                ], 500);
            }

            $data = $response->json();

            // b. 書籍が見つからない場合
            if (empty($data['totalItems']) || empty($data['items'])) {
                return response()->json([
                    'error' => '書籍が見つかりませんでした。'
                ], 404);
            }

            $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

            // 日付フォーマットの整形（Blade側の Date パースでずれないよう YYYY-MM-DD に統一）
            $rawDate = $volumeInfo['publishedDate'] ?? '';
            $publishedDate = '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                $publishedDate = $rawDate;
            } elseif (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
                $publishedDate = $rawDate . '-01';
            } elseif (preg_match('/^\d{4}$/', $rawDate)) {
                $publishedDate = $rawDate . '-01-01';
            }

            // 成功時レスポンス（error キーは含めない）
            return response()->json([
                'title' => $volumeInfo['title'] ?? '',
                'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '',
                'published_date' => $publishedDate,
                'description' => $volumeInfo['description'] ?? '',
                'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? $volumeInfo['imageLinks']['smallThumbnail'] ?? '',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Google Books API Error: ' . $e->getMessage());

            // d. 通信エラー等の例外
            return response()->json([
                'error' => 'API通信エラーが発生しました。'
            ], 500);
        }
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

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を登録しました。');
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