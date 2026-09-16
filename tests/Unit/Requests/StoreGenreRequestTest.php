<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreGenreRequest;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreGenreRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 正しい入力値
     */
    public function test_valid_data_passes_validation(): void
    {
        $data = [
            'name' => 'ファンタジー',
        ];

        $request = new StoreGenreRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * 異常系: ジャンル名が未入力の場合
     */
    public function test_name_required_validation_fails(): void
    {
        $data = [
            'name' => '',
        ];

        $request = new StoreGenreRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * 異常系: ジャンル名の重複登録拒否
     */
    public function test_name_unique_validation_fails(): void
    {
        Genre::factory()->create(['name' => 'SF']);

        $data = [
            'name' => 'SF',
        ];

        $request = new StoreGenreRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }
}