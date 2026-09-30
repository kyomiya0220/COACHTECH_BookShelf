<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * 通知一覧表示
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // ログインユーザーの全通知を最新順で取得
        $notifications = $user->notifications()->latest()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 指定通知の既読化処理
     */
    public function read(DatabaseNotification $notification)
    {
        // 認可チェック（所有者本人以外は 403 Forbidden）
        if ($notification->notifiable_id !== Auth::id()) {
            abort(403, 'この操作を行う権限がありません。');
        }

        // 未読の場合のみ read_at を now() に更新
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
