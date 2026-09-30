<?php

namespace Tests\Feature\Batch;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\PlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlanBatchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 期限切れの計画（reading）が expired に一括更新される検証
     */
    public function test_expired_reading_plans_are_updated_to_expired(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        // 過去の日付（期限切れ）
        $expiredPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => 'reading',
            'target_date' => now()->subDay()->format('Y-m-d'),
        ]);

        // 未来の日付（期限内）
        $validPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => 'reading',
            'target_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        // 日次バッチコマンドを実行
        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        // 期限切れの計画が expired に更新されていることを検証
        $this->assertDatabaseHas('reading_plans', [
            'id' => $expiredPlan->id,
            'status' => 'overdue',
        ]);

        // 期限内の計画は変更されていないことを検証
        $this->assertDatabaseHas('reading_plans', [
            'id' => $validPlan->id,
            'status' => 'reading',
        ]);
    }

    /**
     * 期日3日前 / 当日 / 期限切れ3日後の対象計画に対する通知送信検証
     */
    public function test_reminder_notifications_are_sent_for_target_reading_plans(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $targetDates = [
            now()->addDays(3)->format('Y-m-d'), // 3日前
            now()->format('Y-m-d'),              // 当日
            now()->subDays(3)->format('Y-m-d'), // 期限切れ3日後
        ];

        foreach ($targetDates as $date) {
            // ユニーク制約エラー防止のためループ内で別々の Book を作成
            $book = Book::factory()->create();

            ReadingPlan::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'status' => 'reading',
                'target_date' => $date,
            ]);
        }

        // 日次バッチコマンドを実行
        $this->artisan('reading-plans:process-daily')
            ->assertSuccessful();

        // Notification::fake() を使って PlanReminderNotification が送信されたことを検証
        Notification::assertSentTo(
            [$user],
            PlanReminderNotification::class
        );
    }
}
