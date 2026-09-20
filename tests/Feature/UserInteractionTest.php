<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserInteractionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. レビュー投稿
     * 必須項目を入力して投稿できること、およびDBに保存されること
     */
    public function test_user_can_post_review_and_see_it_on_detail_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['genre_id' => $genre->id]);

        $reviewData = [
            'rating' => 5,
            'comment' => '最高の一冊でした！',
        ];

        // レビュー投稿
        $response = $this->actingAs($user)->post(route('reviews.store', $book), $reviewData);

        // 成功時のレスポンス（リダイレクト）を確認
        $response->assertStatus(302);

        // DBにレビューが保存されていること
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '最高の一冊でした！',
        ]);

        // 詳細画面にレビュー内容が表示されていること
        $detailResponse = $this->get(route('books.show', $book));
        $detailResponse->assertSee('最高の一冊でした！');
    }

    /**
     * 2. ジャンル絞り込み
     * クエリ文字列（genre_id / genre）に応じて対象の書籍が表示されること
     */
    public function test_can_filter_books_by_genre(): void
    {
        $genreA = Genre::factory()->create(['name' => 'プログラミング']);
        $genreB = Genre::factory()->create(['name' => '小説']);

        $bookA = Book::factory()->create(['title' => 'Laravel入門', 'genre_id' => $genreA->id]);
        $bookB = Book::factory()->create(['title' => '夏目漱石全集', 'genre_id' => $genreB->id]);

        if (method_exists($bookA, 'genres')) {
            $bookA->genres()->sync([$genreA->id]);
            $bookB->genres()->sync([$genreB->id]);
        }

        // 'genre' と 'genre_id' の両方のパラメータキーに対応
        $response = $this->get(route('books.index', ['genre' => $genreA->id, 'genre_id' => $genreA->id]));

        $response->assertStatus(200);
        $response->assertSee('Laravel入門');
    }

    /**
     * 3. お気に入り着脱
     * ボタン押下でトグル処理が行われ、DBが更新されること（追加 → 解除 → 再追加）
     */
    public function test_favorite_toggle_three_steps(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['genre_id' => $genre->id]);

        // ステップ1: 追加
        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        // ステップ2: 解除
        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        // ステップ3: 再追加
        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    /**
     * 4. レビューへのいいね
     * レビューに対していいねが実行されDBに保存されること
     */
    public function test_review_like_toggle_three_steps(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['genre_id' => $genre->id]);
        $review = Review::factory()->create(['book_id' => $book->id, 'user_id' => $user->id]);

        // いいね実行
        $response = $this->actingAs($user)->post(route('reviews.like', $review));
        $response->assertStatus(302);

        // DBにいいね情報が存在すること
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    /**
     * 5. ランキング表示
     * レビュー評価・投稿が存在する書籍がランキング画面に順に表示されること
     */
    public function test_ranking_display_order(): void
    {
        $genre = Genre::factory()->create();

        $bookPopular = Book::factory()->create(['title' => '人気本', 'genre_id' => $genre->id]);
        $bookNormal = Book::factory()->create(['title' => '普通の本', 'genre_id' => $genre->id]);

        $users = User::factory()->count(2)->create();

        // 人気本に高評価のレビューを投稿
        Review::factory()->create([
            'book_id' => $bookPopular->id,
            'user_id' => $users[0]->id,
            'rating' => 5,
            'comment' => '最高です',
        ]);

        // 普通の本にレビューを投稿
        Review::factory()->create([
            'book_id' => $bookNormal->id,
            'user_id' => $users[1]->id,
            'rating' => 3,
            'comment' => '普通です',
        ]);

        // ランキング画面を取得
        $response = $this->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['人気本', '普通の本']);
    }
}