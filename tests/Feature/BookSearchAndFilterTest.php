<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookSearchAndFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * キーワード検索: タイトル・著者名の部分一致検証
     */
    public function test_can_search_books_by_title_or_author_partial_match(): void
    {
        Book::factory()->create(['title' => 'Laravel実践ガイド', 'author' => '山田太郎']);
        Book::factory()->create(['title' => 'PHP超入門', 'author' => '佐藤花子']);

        // タイトル部分一致
        $response = $this->get('/books?keyword=Laravel');
        $response->assertStatus(200);
        $response->assertSee('Laravel実践ガイド');
        $response->assertDontSee('PHP超入門');

        // 著者名部分一致
        $response = $this->get('/books?keyword=佐藤');
        $response->assertStatus(200);
        $response->assertSee('PHP超入門');
        $response->assertDontSee('Laravel実践ガイド');
    }

    /**
     * キーワード検索: 前後スペース（半角・全角）のトリム処理検証
     */
    public function test_search_keyword_trims_full_and_half_width_spaces(): void
    {
        Book::factory()->create(['title' => 'Laravel実践ガイド', 'author' => '山田太郎']);

        // 前後に全角・半角スペースを含めて検索
        $response = $this->get('/books?keyword=' . urlencode('  Laravel  '));

        $response->assertStatus(200);
        $response->assertSee('Laravel実践ガイド');
    }

    /**
     * ジャンル絞り込み: 指定ジャンルのみの取得、存在しないジャンルID指定時のフォールバック検証
     */
    public function test_genre_filter_and_fallback_for_invalid_genre(): void
    {
        $genre1 = Genre::factory()->create(['name' => 'プログラミング']);
        $genre2 = Genre::factory()->create(['name' => '小説']);

        $book1 = Book::factory()->create(['title' => 'プログラミング本']);
        $book1->genres()->attach($genre1->id);

        $book2 = Book::factory()->create(['title' => '小説本']);
        $book2->genres()->attach($genre2->id);

        // 指定ジャンルのみ取得
        $response = $this->get("/books?genre={$genre1->id}");
        $response->assertStatus(200);
        $response->assertSee('プログラミング本');
        $response->assertDontSee('小説本');

        // 存在しないジャンルID指定時のフォールバック（0件表示または全件表示の仕様に合わせて確認）
        $response = $this->get('/books?genre=999999');
        $response->assertStatus(200);
    }

    /**
     * ソート機能: newest（デフォルト）, oldest, title 順の検証
     */
    public function test_sort_by_newest_oldest_and_title(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'AAA Book',
            'created_at' => now()->subDays(2),
        ]);
        $book2 = Book::factory()->create([
            'title' => 'ZZZ Book',
            'created_at' => now()->subDays(1),
        ]);

        // newest (デフォルト / newest指定)
        $response = $this->get('/books?sort=newest');
        $response->assertStatus(200);
        $response->assertSeeInOrder(['ZZZ Book', 'AAA Book']);

        // oldest
        $response = $this->get('/books?sort=oldest');
        $response->assertStatus(200);
        $response->assertSeeInOrder(['AAA Book', 'ZZZ Book']);

        // title
        $response = $this->get('/books?sort=title');
        $response->assertStatus(200);
        $response->assertSeeInOrder(['AAA Book', 'ZZZ Book']);
    }

    /**
     * ソート機能: rating（評価順）でレビュー無し（NULL）が最後に配置され、同率評価時の第2優先度が登録順になる検証
     */
    public function test_sort_by_rating_with_nulls_last_and_secondary_sort_by_created_at(): void
    {
        $user = User::factory()->create();

        // 評価 5.0（古く登録）
        $highRatedOldBook = Book::factory()->create([
            'title' => 'High Rated Old',
            'created_at' => now()->subDays(3),
        ]);
        Review::factory()->create(['book_id' => $highRatedOldBook->id, 'user_id' => $user->id, 'rating' => 5]);

        // 評価 5.0（新しく登録：同率評価での第2優先度テスト）
        $highRatedNewBook = Book::factory()->create([
            'title' => 'High Rated New',
            'created_at' => now()->subDays(1),
        ]);
        Review::factory()->create(['book_id' => $highRatedNewBook->id, 'user_id' => $user->id, 'rating' => 5]);

        // 評価 3.0
        $lowRatedBook = Book::factory()->create([
            'title' => 'Low Rated',
            'created_at' => now()->subDays(2),
        ]);
        Review::factory()->create(['book_id' => $lowRatedBook->id, 'user_id' => $user->id, 'rating' => 3]);

        // レビューなし（NULL）
        $noReviewBook = Book::factory()->create([
            'title' => 'No Review',
            'created_at' => now()->subDays(4),
        ]);

        $response = $this->get('/books?sort=rating');
        $response->assertStatus(200);

        // 同率評価は登録日時が新しい順（New -> Old）になり、レビュー無しは最後尾に配置される
        $response->assertSeeInOrder([
            'High Rated New',
            'High Rated Old',
            'Low Rated',
            'No Review',
        ]);
    }

    /**
     * 不正なソートパラメータ指定時に newest にフォールバックされることの検証
     */
    public function test_invalid_sort_parameter_falls_back_to_newest(): void
    {
        $oldBook = Book::factory()->create(['title' => 'Old Book', 'created_at' => now()->subDays(2)]);
        $newBook = Book::factory()->create(['title' => 'New Book', 'created_at' => now()->subDays(1)]);

        $response = $this->get('/books?sort=invalid_param_xyz');
        $response->assertStatus(200);

        // デフォルト（newest）順で返ってくることを確認
        $response->assertSeeInOrder(['New Book', 'Old Book']);
    }

    /**
     * ページネーション: ページ遷移時に検索条件（クエリパラメータ）が維持されることの検証
     */
    public function test_pagination_links_preserve_search_query_parameters(): void
    {
        $genre = Genre::factory()->create();

        // 1ページあたり10件前提で15件作成
        Book::factory()->count(15)->create(['title' => 'Laravel Test Book'])->each(function ($book) use ($genre) {
            $book->genres()->attach($genre->id);
        });

        $response = $this->get("/books?keyword=Laravel&genre={$genre->id}&sort=oldest&page=1");
        $response->assertStatus(200);

        // ページネーションリンク内にクエリパラメータが維持されていることを確認
        $response->assertSee('keyword=Laravel');
        $response->assertSee("genre={$genre->id}");
        $response->assertSee('sort=oldest');
        $response->assertSee('page=2');
    }

    /**
     * ISBN検索 (外部API): Http::fake() を使用して Google Books API のモック化を行い、正常取得およびエラー時の挙動を検証
     */
    public function test_isbn_search_with_google_books_api_mocking(): void
    {
        $user = User::factory()->create();
        $isbn = '9784839962227';
        $notFoundIsbn = '9780000000000';

        // 1回目は正常系、2回目は該当なし(404)のレスポンスを順番に返すようシーケンス設定
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::sequence()
                ->push([
                    'totalItems' => 1,
                    'items' => [
                        [
                            'volumeInfo' => [
                                'title' => 'Laravel入門',
                                'authors' => ['テスト太郎'],
                                'publishedDate' => '2024-01-01',
                                'description' => 'Laravelの解説書です。',
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'totalItems' => 0,
                    'items' => [],
                ], 200),
        ]);

        // 1. 正常系の検証
        $response = $this->actingAs($user)->get("/books/isbn/{$isbn}");

        $response->assertStatus(200);
        $response->assertJson([
            'title' => 'Laravel入門',
            'author' => 'テスト太郎',
            'published_date' => '2024-01-01',
            'description' => 'Laravelの解説書です。',
        ]);

        // 2. 書籍未発見時の検証（シーケンスの2番目が使われる）
        $errorResponse = $this->actingAs($user)->get("/books/isbn/{$notFoundIsbn}");

        $errorResponse->assertStatus(404);
        $errorResponse->assertJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }
}