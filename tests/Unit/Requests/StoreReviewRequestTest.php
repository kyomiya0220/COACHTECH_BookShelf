<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreReviewRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 正しい入力値 (評価1~5)
     */
    public function test_valid_data_passes_validation(): void
    {
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '素晴らしい本でした。',
        ];

        $request = new StoreReviewRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * 異常系: 評価（星の数）が範囲外 (0や6)
     */
    public function test_rating_out_of_range_validation_fails(): void
    {
        $book = Book::factory()->create();

        // 0の場合
        $request = new StoreReviewRequest();
        $validatorMin = Validator::make([
            'book_id' => $book->id,
            'rating' => 0,
            'comment' => 'テスト',
        ], $request->rules());

        $this->assertTrue($validatorMin->fails());
        $this->assertArrayHasKey('rating', $validatorMin->errors()->toArray());

        // 6の場合
        $validatorMax = Validator::make([
            'book_id' => $book->id,
            'rating' => 6,
            'comment' => 'テスト',
        ], $request->rules());

        $this->assertTrue($validatorMax->fails());
        $this->assertArrayHasKey('rating', $validatorMax->errors()->toArray());
    }

    /**
     * 異常系: 本文の文字数オーバー (1000文字超など)
     */
    public function test_comment_max_length_validation_fails(): void
    {
        $book = Book::factory()->create();

        $data = [
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => str_repeat('a', 1001),
        ];

        $request = new StoreReviewRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('comment', $validator->errors()->toArray());
    }
}