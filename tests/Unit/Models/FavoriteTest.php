<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: お気に入りが正常に登録できる
     */
    public function test_favorite_can_be_created(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * 異常系: 同一ユーザー×同一書籍の重複登録を防止（複合ユニーク制約）
     */
    public function test_duplicate_favorite_is_prevented(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 1回目の登録
        Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 2回目の登録（例外が発生することを期待）
        $this->expectException(QueryException::class);

        Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }
}