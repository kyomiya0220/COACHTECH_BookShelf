<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private array $headers = [
        'Accept' => 'application/json',
    ];

    /**
     * 未認証アクセス (401): トークンなしでの POST リクエストが 401 Unauthorized を返すことの検証
     */
    public function test_unauthenticated_user_cannot_create_book(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784839962227',
            'published_date' => '2024-01-01',
            'description' => 'テスト概要',
            'genre_id' => $genre->id,
        ], $this->headers);

        $response->assertStatus(401);
    }

    /**
     * 未認証アクセス (401): トークンなしでの PUT リクエストが 401 Unauthorized を返すことの検証
     */
    public function test_unauthenticated_user_cannot_update_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後のタイトル',
        ], $this->headers);

        $response->assertStatus(401);
    }

    /**
     * 未認証アクセス (401): トークンなしでの DELETE リクエストが 401 Unauthorized を返すことの検証
     */
    public function test_unauthenticated_user_cannot_delete_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}", [], $this->headers);

        $response->assertStatus(401);
    }

    /**
     * 認可エラー (403): 認証済みだが「書籍所有者ではないユーザー」が PUT を実行した際に 403 Forbidden を返すことの検証
     */
    public function test_user_cannot_update_other_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '不正更新タイトル',
        ], $this->headers);

        $response->assertStatus(403);
    }

    /**
     * 認可エラー (403): 認証済みだが「書籍所有者ではないユーザー」が DELETE を実行した際に 403 Forbidden を返すことの検証
     */
    public function test_user_cannot_delete_other_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/books/{$book->id}", [], $this->headers);

        $response->assertStatus(403);
    }

    /**
     * 正常系 (201): 認証済みユーザーによる POST（書籍作成）が 201 Created を返すことの検証
     */
    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $payload = [
            'title' => '新規登録書籍',
            'author' => '著者名',
            'isbn' => '9784839962227',
            'published_date' => '2024-01-01',
            'description' => '説明文',
            'genre_id' => $genre->id,
        ];

        $response = $this->postJson('/api/v1/books', $payload, $this->headers);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'title' => '新規登録書籍',
            'user_id' => $user->id,
        ]);
    }

    /**
     * 正常系 (200): 所有者本人による PUT（更新）が 200 OK を返すことの検証
     */
    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新されたタイトル',
        ], $this->headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新されたタイトル',
        ]);
    }

    /**
     * 正常系 (204): 所有者本人による DELETE（削除）が 204 No Content を返すことの検証
     */
    public function test_owner_can_delete_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/books/{$book->id}", [], $this->headers);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }
}