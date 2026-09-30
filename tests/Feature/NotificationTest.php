<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーの通知一覧取得検証
     */
    public function test_user_can_view_notifications_list(): void
    {
        $user = User::factory()->create();

        // ビューが参照するキー（titleやmessage等）に合わせてデータを渡す
        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\PlanReminderNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => [
                'title' => '読書計画の通知',
                'message' => '読書計画の目標期日が近づいています。',
            ],
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
        // 「既読にする」ボタンや通知タイトルなど一覧に表示されるテキストを確認
        $response->assertSee('既読にする');
    }

    /**
     * 既読化ボタン押下時の read_at 更新 ＆ フラッシュメッセージ検証
     */
    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\PlanReminderNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['message' => 'テスト通知'],
            'read_at' => null,
        ]);

        // PATCH ではなく POST を使用
        $response = $this->actingAs($user)->post("/notifications/{$notification->id}/read");

        $response->assertRedirect();

        // read_at が更新されていることを検証
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * 他人の通知を既読化しようとした場合の 403 Forbidden 検証
     */
    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $notification = DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\Notifications\PlanReminderNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->id,
            'data' => ['message' => '所有者宛ての通知'],
            'read_at' => null,
        ]);

        // PATCH ではなく POST を使用
        $response = $this->actingAs($otherUser)->post("/notifications/{$notification->id}/read");

        $response->assertStatus(403);
        $this->assertNull($notification->fresh()->read_at);
    }
}
