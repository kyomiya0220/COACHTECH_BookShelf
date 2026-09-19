<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AP01: 書籍一覧取得API
     */
    public function test_can_get_book_list(): void
    {
        $genre = Genre::factory()->create();
        Book::factory()->count(3)->create()->each(function ($book) use ($genre) {
            $book->genres()->attach($genre->id);
        });

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'author', 'isbn', 'genres', 'avg_rating', 'reviews_count']
                ]
            ]);
    }

    /**
     * AP02: 書籍詳細取得API
     */
    public function test_can_get_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id);
    }

    /**
     * AP02 異常系: 存在しないID指定時は 404
     */
    public function test_returns_404_when_book_not_found(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertStatus(404);
    }

    /**
     * AP03: 書籍登録API
     */
    public function test_can_create_book(): void
    {
        $this->withoutExceptionHandling();
        $user = \App\Models\User::factory()->create();
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'APIテスト本',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'description' => '説明文',
            'genre_id' => $genre->id,
        ];

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'APIテスト本');

        $this->assertDatabaseHas('books', ['isbn' => '9784123456789']);
    }

    /**
     * AP04: 書籍更新API
     */
    public function test_can_update_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', '更新後のタイトル');
    }

    /**
     * AP05: 書籍削除API
     */
    public function test_can_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }
}
