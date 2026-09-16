<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreBookRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreBookRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 正しい入力値
     */
    public function test_valid_data_passes_validation(): void
    {
        $genre = Genre::factory()->create();

        $data = [
            'title' => 'テスト書籍タイトル',
            'author' => 'テスト著者',
            'isbn' => '9784123456789', // 13桁数字
            'published_date' => '2026-01-01',
            'genres' => [$genre->id], // 配列形式
        ];

        $request = new StoreBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * 異常系: 必須項目が未入力の場合
     */
    public function test_required_fields_validation_fails(): void
    {
        $data = [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'genres' => [],
        ];

        $request = new StoreBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('author', $validator->errors()->toArray());
        $this->assertArrayHasKey('isbn', $validator->errors()->toArray());
        $this->assertArrayHasKey('published_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('genres', $validator->errors()->toArray());
    }

    /**
     * 異常系: タイトルの文字数制限オーバー（255文字超）
     */
    public function test_title_max_length_validation_fails(): void
    {
        $genre = Genre::factory()->create();

        $data = [
            'title' => str_repeat('a', 256),
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $request = new StoreBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    /**
     * 異常系: 存在しないジャンルID（外部キーエラー）
     */
    public function test_non_existent_genre_id_validation_fails(): void
    {
        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'genres' => [99999], // 存在しないジャンルID
        ];

        $request = new StoreBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('genres.0', $validator->errors()->toArray());
    }
}