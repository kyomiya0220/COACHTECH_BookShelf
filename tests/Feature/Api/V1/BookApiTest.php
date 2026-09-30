<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧取得
     */
    public function test_can_get_book_list(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $books = Book::factory()->count(3)->create([
            'user_id' => $user->id,
            'genre_id' => $genre->id,
        ]);

        foreach ($books as $book) {
            if (method_exists($book, 'genres')) {
                $book->genres()->attach($genre->id);
            }
        }

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'author', 'isbn'],
                ],
            ]);
    }

    /**
     * 書籍詳細取得
     */
    public function test_can_get_book_detail(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'genre_id' => $genre->id,
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id);
    }

    /**
     * 存在しない書籍の取得（404）
     */
    public function test_returns_404_when_book_not_found(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertStatus(404)
            ->assertExactJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    /**
     * 書籍新規作成
     */
    public function test_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $bookData = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'genre_id' => $genre->id,
        ];

        $response = $this->postJson('/api/v1/books', $bookData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'テスト書籍');
    }

    /**
     * 書籍更新
     */
    public function test_can_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'genre_id' => $genre->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => is_string($book->published_date)
                ? $book->published_date
                : $book->published_date->format('Y-m-d'),
            'genre_id' => $genre->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', '更新後のタイトル');
    }

    /**
     * 書籍削除
     */
    public function test_can_delete_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'genre_id' => $genre->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
