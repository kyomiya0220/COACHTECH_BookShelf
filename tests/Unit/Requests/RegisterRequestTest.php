<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 正しい入力値
     */
    public function test_valid_data_passes_validation(): void
    {
        $data = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * 異常系: 必須項目が未入力の場合
     */
    public function test_required_fields_validation_fails(): void
    {
        $data = [
            'name' => '',
            'email' => '',
            'password' => '',
        ];

        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * 異常系: メールアドレス形式不正・重複チェック
     */
    public function test_email_format_and_unique_validation_fails(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        // 形式不正
        $request = new RegisterRequest();
        $validatorInvalid = Validator::make(['email' => 'invalid-email'], $request->rules());
        $this->assertArrayHasKey('email', $validatorInvalid->errors()->toArray());

        // 重複
        $validatorDuplicate = Validator::make(['email' => 'existing@example.com'], $request->rules());
        $this->assertArrayHasKey('email', $validatorDuplicate->errors()->toArray());
    }

    /**
     * 異常系: パスワードの文字数制限
     */
    public function test_password_length_validation_fails(): void
    {
        $data = [
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        $request = new RegisterRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}