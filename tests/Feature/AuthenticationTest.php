<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * チェックリスト 1-1: 新規登録後に自動ログイン（またはログイン画面へ遷移）できる
     */
    public function test_user_can_register_and_be_authenticated(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'register_test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // 登録後にログイン状態になっていることを確認
        $this->assertAuthenticated();

        // リダイレクト先の確認（トップページ '/books' または '/dashboard' など）
        $response->assertRedirect();
    }

    /**
     * チェックリスト 1-2: 正しい資格情報でログインできる
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect();
    }

    /**
     * チェックリスト 2: 未ログイン時に保護されたページへアクセスするとログイン画面へリダイレクトされる
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        // 保護されたページ（書籍新規作成画面など）へのアクセス
        $response = $this->get('/books/create');

        $response->assertRedirect('/login');
    }

    /**
     * チェックリスト 3: ログイン状態の際、認証必須画面へ正常にアクセスできる (200 OK)
     */
    public function test_authenticated_user_can_access_protected_pages(): void
    {
        $user = User::factory()->create();

        // ログイン状態にしてアクセス
        $response = $this->actingAs($user)->get('/books/create');

        $response->assertStatus(200);
    }
}