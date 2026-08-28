<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        // 作成したユーザーインスタンスを変数 $user に受け取る
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 作成したユーザーでログイン処理
        Auth::login($user);

        // フラッシュメッセージを付与してリダイレクト
        return redirect()->route('books.index')
            ->with('success', 'ユーザー登録が完了しました。');
    }
}