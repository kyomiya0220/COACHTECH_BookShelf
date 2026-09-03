<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // ログイン処理（POST）
    public function login(LoginRequest $request)
    {
        // バリデーション成功後のデータを取得
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // ログイン成功時のフラッシュメッセージ
            return redirect()->intended(route('books.index'))
                ->with('success', 'ログインに成功しました。');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません。',
        ])->onlyInput('email');

    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト時のフラッシュメッセージ
        return redirect()->route('login')
            ->with('success', 'ログアウトしました。');
    }
}