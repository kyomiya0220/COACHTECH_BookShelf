<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * チェックリスト1: 書籍新規登録
     * 正しい入力値で登録を実行した際、DBにデータが保存され、一覧画面にリダイレクトされること
     */
    public function test_user_can_create_book_and_redirect_with_message(): void
    {

        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'Web登録テスト本',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'description' => '登録テスト用の概要です。',
            'genres' => [$genre->id], // 配列形式で渡す
        ];

        $response = $this->actingAs($user)->post(route('books.store'), $data);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Web登録テスト本',
            'isbn' => '9784123456789',
        ]);

        $response->assertRedirect(route('books.index'));
    }

    /**
     * チェックリスト2: 書籍編集・削除（本人）
     * 自分の投稿した書籍の更新・削除が正常に反映されること
     */
    public function test_owner_can_update_and_delete_own_book(): void
    {
        $this->withoutExceptionHandling();

        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'genre_id' => $genre->id]);

        // 本人による更新
        $updateResponse = $this->actingAs($owner)->put(route('books.update', $book), [
            'title' => '更新されたタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'genres' => [$genre->id],
        ]);

        $updateResponse->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新されたタイトル']);

        // 本人による削除
        $deleteResponse = $this->actingAs($owner)->delete(route('books.destroy', $book));

        $deleteResponse->assertRedirect(route('books.index'))
            ->assertSessionHas('success', '書籍を削除しました。');
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * チェックリスト3: 書籍編集・削除（他者）
     * 他人の書籍の編集・削除が拒否されること（403 Forbidden）
     */
    public function test_other_user_cannot_update_or_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create(['user_id' => $owner->id, 'genre_id' => $genre->id]);

        // 他人（ログイン済み）による更新試行 -> 403
        $updateResponse = $this->actingAs($otherUser)->put(route('books.update', $book), [
            'title' => '不正更新タイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => $book->published_date,
            'genres' => [$genre->id],
        ]);

        $updateResponse->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => $book->title]);

        // 他人（ログイン済み）による削除試行 -> 403
        $deleteResponse = $this->actingAs($otherUser)->delete(route('books.destroy', $book));

        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}