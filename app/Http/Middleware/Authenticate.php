<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            // 未ログインでアクセスされた際にメッセージをセッションに詰める
            session()->flash('status', 'この機能を利用するにはログインが必要です。');

            return route('login');
        }

        return null;
    }
}
