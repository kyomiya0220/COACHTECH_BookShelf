<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateBookRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateBookRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 正しい入力値
     */
    public function test_valid_data_passes_validation(): void
    {
        $genre = Genre::factory()->create();

        $data = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $request = new UpdateBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * 異常系: 必須項目の不備
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

        $request = new UpdateBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('author', $validator->errors()->toArray());
        $this->assertArrayHasKey('isbn', $validator->errors()->toArray());
        $this->assertArrayHasKey('published_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('genres', $validator->errors()->toArray());
    }

    /**
     * 異常系: タイトル・文字数制限オーバー（255文字超）
     */
    public function test_title_max_length_validation_fails(): void
    {
        $genre = Genre::factory()->create();

        $data = [
            'title' => str_repeat('a', 256),
            'author' => '更新後の著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ];

        $request = new UpdateBookRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }
}