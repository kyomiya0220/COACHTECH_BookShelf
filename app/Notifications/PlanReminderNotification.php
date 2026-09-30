<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PlanReminderNotification extends Notification
{
    use Queueable;

    protected $readingPlan;

    protected $type;

    public function __construct(ReadingPlan $readingPlan, string $type)
    {
        $this->readingPlan = $readingPlan;
        $this->type = $type; // '3days_before', 'today', '3days_after'
    }

    // 仕様通りの DatabaseChannel（データベース通知）を使用
    public function via($notifiable): array
    {
        return ['database'];
    }

    // データベース（notificationsテーブル）に保存される通知データ
    public function toArray($notifiable): array
    {
        $bookTitle = $this->readingPlan->book->title ?? '書籍';

        $messages = [
            '3days_before' => "【リマインド】「{$bookTitle}」の読書期日まであと3日です！",
            'today' => "【本日期日】「{$bookTitle}」の読書期日は本日です！",
            '3days_after' => "【期限切れ】「{$bookTitle}」の期日から3日が経過しました。読書を再開しましょう！",
        ];

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_title' => $bookTitle,
            'type' => $this->type,
            'message' => $messages[$this->type] ?? "「{$bookTitle}」のリマインド通知です。",
        ];
    }
}
