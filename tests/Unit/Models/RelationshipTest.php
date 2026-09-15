<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Userモデルのリレーションテスト
     */
    public function test_user_has_many_books_reviews_and_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create(['user_id' => $user->id]);
        $review = Review::factory()->create(['user_id' => $user->id]);
        $favorite = Favorite::factory()->create(['user_id' => $user->id]);

        $user->refresh();

        $this->assertInstanceOf(Collection::class, $user->books);
        $this->assertTrue($user->books->contains('id', $book->id));

        $this->assertInstanceOf(Collection::class, $user->reviews);
        $this->assertTrue($user->reviews->contains('id', $review->id));

        $this->assertInstanceOf(Collection::class, $user->favorites);
        $this->assertTrue($user->favorites->contains(fn($f) => $f->user_id === $user->id && $f->book_id === $favorite->book_id));
    }

    /**
     * Bookモデルのリレーションテスト
     */
    public function test_book_belongs_to_user_and_genre_and_has_many_reviews_and_favorites(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
            'genre_id' => $genre->id,
        ]);

        $review = Review::factory()->create(['book_id' => $book->id]);
        $favorite = Favorite::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(User::class, $book->user);
        $this->assertEquals($user->id, $book->user->id);

        $this->assertInstanceOf(Genre::class, $book->genre);
        $this->assertEquals($genre->id, $book->genre->id);

        $this->assertInstanceOf(Collection::class, $book->reviews);
        $this->assertTrue($book->reviews->contains('id', $review->id));

        $this->assertInstanceOf(Collection::class, $book->favorites);
        $this->assertTrue($book->favorites->contains(fn($f) => $f->book_id === $book->id));
    }

    /**
     * Genreモデルのリレーションテスト
     */
    public function test_genre_has_many_books(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['genre_id' => $genre->id]);

        $this->assertInstanceOf(Collection::class, $genre->refresh()->books);
        $this->assertTrue($genre->books->contains('id', $book->id));
    }

    /**
     * Reviewモデルのリレーションテスト
     */
    public function test_review_belongs_to_user_and_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);

        $this->assertInstanceOf(Book::class, $review->book);
        $this->assertEquals($book->id, $review->book->id);
    }

    /**
     * Favoriteモデルのリレーションテスト
     */
    public function test_favorite_belongs_to_user_and_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertInstanceOf(User::class, $favorite->user);
        $this->assertEquals($user->id, $favorite->user->id);

        $this->assertInstanceOf(Book::class, $favorite->book);
        $this->assertEquals($book->id, $favorite->book->id);
    }
}