<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧取得
     */
    public function test_get_books_list_successfully(): void
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
                    '*' => ['id', 'title', 'author', 'isbn']
                ]
            ]);
    }

    /**
     * 書籍詳細取得
     */
    public function test_get_book_detail_successfully(): void
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
                'error' => '書籍が見つかりませんでした。'
            ]);
    }
}