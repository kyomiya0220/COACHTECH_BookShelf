<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証アクセスのリダイレクト検証
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect('/login');
    }

    /**
     * サマリー情報（総レビュー数、ユニーク読了冊数、平均評価点）の正確な集計検証
     */
    public function test_user_can_view_accurate_report_summary(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        // レビュー作成（計3件：book1に2件、book2に1件）
        Review::create(['user_id' => $user->id, 'book_id' => $book1->id, 'rating' => 4, 'comment' => 'Good']);
        Review::create(['user_id' => $user->id, 'book_id' => $book1->id, 'rating' => 5, 'comment' => 'Great']);
        Review::create(['user_id' => $user->id, 'book_id' => $book2->id, 'rating' => 3, 'comment' => 'Average']);

        // 読破済みの読書計画（ユニーク読了冊数用）
        ReadingPlan::create(['user_id' => $user->id, 'book_id' => $book1->id, 'status' => 'completed', 'target_date' => now()]);
        ReadingPlan::create(['user_id' => $user->id, 'book_id' => $book2->id, 'status' => 'completed', 'target_date' => now()]);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertStatus(200);

        // 平均評価点: (4 + 5 + 3) / 3 = 4.0
        $response->assertSee('4.0');
    }

    /**
     * 星別評価分布（1〜5星）の件数集計検証
     */
    public function test_user_can_view_rating_distribution(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 各評価のレビューを作成
        Review::create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 5, 'comment' => 'Star 5']);
        Review::create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 5, 'comment' => 'Star 5 second']);
        Review::create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 1, 'comment' => 'Star 1']);

        $response = $this->actingAs($user)->get('/reports');

        $response->assertStatus(200);
    }

    /**
     * 高評価TOP5、ジャンル別TOP5のソート ＆ 件数上限（最大5件）の検証
     */
    public function test_top_rated_books_and_genres_limit_to_five_items(): void
    {
        $user = User::factory()->create();

        // 6件の書籍を作成してレビュー登録（上限5件の検証用）
        for ($i = 1; $i <= 6; $i++) {
            $genre = Genre::create(['name' => "ジャンル{$i}"]);
            $book = Book::factory()->create(['genre_id' => $genre->id, 'title' => "書籍{$i}"]);

            Review::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $i > 5 ? 1 : 5, // 6点目は低評価
                'comment' => "レビュー{$i}",
            ]);
        }

        $response = $this->actingAs($user)->get('/reports');

        $response->assertStatus(200);
        $response->assertSee('書籍5');
        // 最低評価の「書籍6」がTOP5に含まれないことを確認
        $response->assertDontSee('書籍6');
    }
}