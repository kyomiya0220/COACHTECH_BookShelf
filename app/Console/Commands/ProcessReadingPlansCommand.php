<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\PlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessReadingPlansCommand extends Command
{
    protected $signature = 'reading-plans:process-daily';

    protected $description = '期日経過の自動更新およびリマインド通知の送信';

    public function handle()
    {
        $today = Carbon::today('Asia/Tokyo');

        // ① 期日経過した in_progress(reading) 計画を一括 Expired(overdue) 化
        ReadingPlan::whereIn('status', [ReadingPlanStatus::Reading->value, 'in_progress'])
            ->where('target_date', '<', $today->format('Y-m-d'))
            ->update(['status' => ReadingPlanStatus::Overdue->value]);

        // ② 期日 3 日前の計画に通知（予告）
        $threeDaysLater = $today->copy()->addDays(3)->format('Y-m-d');
        $plans3DaysBefore = ReadingPlan::whereIn('status', [ReadingPlanStatus::Reading->value, 'in_progress'])
            ->where('target_date', $threeDaysLater)
            ->get();

        foreach ($plans3DaysBefore as $plan) {
            $plan->user->notify(new PlanReminderNotification($plan, '3days_before'));
        }

        // ③ 期日当日の計画に通知（最終リマインド）
        $plansToday = ReadingPlan::whereIn('status', [ReadingPlanStatus::Reading->value, 'in_progress'])
            ->where('target_date', $today->format('Y-m-d'))
            ->get();

        foreach ($plansToday as $plan) {
            $plan->user->notify(new PlanReminderNotification($plan, 'today'));
        }

        // ④ 期日 3 日後の Expired(overdue) 計画に通知（再エンゲージメント）
        $threeDaysAgo = $today->copy()->subDays(3)->format('Y-m-d');
        $plans3DaysAfter = ReadingPlan::where('status', ReadingPlanStatus::Overdue->value)
            ->where('target_date', $threeDaysAgo)
            ->get();

        foreach ($plans3DaysAfter as $plan) {
            $plan->user->notify(new PlanReminderNotification($plan, '3days_after'));
        }

        $this->info('日次バッチ処理が正常に終了しました。');
    }
}
