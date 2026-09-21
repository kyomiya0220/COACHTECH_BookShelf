<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            // APIリクエスト（/api/*）の場合のみエラーメッセージをJSONで返却
            if ($request->is('api/*') || $request->wantsJson()) {

                // 401: 未認証エラー
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'error' => '認証されていません。ログインしてください。'
                    ], 401);
                }

                // 404: 存在しないリソース
                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'error' => '指定されたリソースが見つかりませんでした。'
                    ], 404);
                }

                // 429: レートリミット（過剰アクセス）
                if ($e instanceof ThrottleRequestsException) {
                    return response()->json([
                        'error' => 'リクエスト上限を超えました。時間をおいて再試行してください。'
                    ], 429);
                }
            }
        });
    }
}
