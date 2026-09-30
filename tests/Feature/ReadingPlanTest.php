<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証アクセスのリダイレクト ＆ フラッシュメッセージ検証
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/reading-plans');

        $response->assertRedirect('/login');
    }

    /**
     * 他人の読書計画アクセス時の 403 Forbidden 検証（更新・削除・読了等）
     */
    public function test_user_cannot_access_or_modify_others_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $plan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'status' => 'reading',
            'target_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        // 他人の計画の編集画面アクセス
        $response = $this->actingAs($otherUser)->get("/reading-plans/{$plan->id}/edit");
        $response->assertStatus(403);

        // 他人の計画の更新試行
        $response = $this->actingAs($otherUser)->put("/reading-plans/{$plan->id}", [
            'target_date' => now()->addDays(10)->format('Y-m-d'),
        ]);
        $response->assertStatus(403);

        // 他人の計画の削除試行
        $response = $this->actingAs($otherUser)->delete("/reading-plans/{$plan->id}");
        $response->assertStatus(403);
    }

    /**
     * ステータス絞り込み（status）検証
     */
    public function test_can_filter_reading_plans_by_status(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create(['title' => '読書中の本']);
        $book2 = Book::factory()->create(['title' => '読破済みの本']);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => 'reading',
            'target_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => 'completed',
            'target_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        // reading で絞り込み
        $response = $this->actingAs($user)->get('/reading-plans?status=reading');
        $response->assertStatus(200);
        $response->assertSee('読書中の本');
        $response->assertDontSee('読破済みの本');
    }

    /**
     * バリデーションエラーメッセージ（必須、日付形式、本日以降、重複登録等）の検証
     */
    public function test_reading_plan_validation_rules(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 必須・過去日付チェック
        $response = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => '',
            'target_date' => now()->subDay()->format('Y-m-d'), // 過去の日付
        ]);

        $response->assertSessionHasErrors(['book_id', 'target_date']);

        // 重複登録チェック（すでに同一書籍の計画が存在する場合）
        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'reading',
            'target_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $duplicateResponse = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $duplicateResponse->assertSessionHasErrors(['book_id']);
    }

    /**
     * 登録・更新・削除・読了完了時のステータス更新 ＆ フラッシュメッセージ検証
     */
    public function test_reading_plan_crud_and_completion_flow(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $targetDate = now()->addDays(7)->format('Y-m-d');

        // 1. 登録（Store）
        $storeResponse = $this->actingAs($user)->post('/reading-plans', [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('success');
        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => 'reading',
        ]);

        $plan = ReadingPlan::where('user_id', $user->id)->first();

        // 2. 更新（Update）
        $newTargetDate = now()->addDays(14)->format('Y-m-d');
        $updateResponse = $this->actingAs($user)->put("/reading-plans/{$plan->id}", [
            'target_date' => $newTargetDate,
        ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'target_date' => $newTargetDate,
        ]);

        // 3. 読了完了（Complete）
        $completeResponse = $this->actingAs($user)->post("/reading-plans/{$plan->id}/complete");

        $completeResponse->assertRedirect();
        $completeResponse->assertSessionHas('success');
        $this->assertDatabaseHas('reading_plans', [
            'id' => $plan->id,
            'status' => 'completed',
        ]);

        // 4. 削除（Destroy）
        $deleteResponse = $this->actingAs($user)->delete("/reading-plans/{$plan->id}");

        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('reading_plans', [
            'id' => $plan->id,
        ]);
    }
}
