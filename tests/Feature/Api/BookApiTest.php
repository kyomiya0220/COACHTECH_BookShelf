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

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のジャンルと書籍を作成
        $genre = Genre::factory()->create();
        Book::factory()->create([
            'id' => 1,
            'genre_id' => $genre->id,
            'title' => 'テスト書籍',
        ]);
    }

    /**
     * @test
     * 正常系: GET /api/books で200と定義通りのJSONが返るか
     */
    public function test_get_books_list_successfully(): void
    {
        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'author', 'isbn']
                ]
            ]);
    }

    /**
     * @test
     * 正常系: GET /api/books/1 で詳細が200で返るか
     */
    public function test_get_book_detail_successfully(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->getJson('/api/v1/books/1');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.title', 'テスト書籍');
    }

    /**
     * @test
     * 異常系(404): 存在しない書籍ID指定時に 404 エラーJSONが返るか
     */
    public function test_returns_404_when_book_not_found(): void
    {
        $response = $this->getJson('/api/v1/books/999');

        $response->assertStatus(404)
            ->assertExactJson([
                'error' => '指定されたリソースが見つかりませんでした。'
            ]);
    }

    /**
     * @test
     * 異常系(401): 認証必須APIに無認証アクセスした際に 401 が返るか
     */
    public function test_returns_401_when_unauthenticated(): void
    {

        $this->markTestSkipped('応用機能で対応予定');

        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/v1/books');

        $response->assertStatus(401)
            ->assertExactJson([
                'error' => '認証されていません。ログインしてください。'
            ]);
    }
}