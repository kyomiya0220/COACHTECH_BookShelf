<?php

use App\Http\Controllers\Api\V1\BookController;

Route::prefix('v1')->group(function () {
    // 基本機能フェーズ：すべて apiResource で定義
    Route::apiResource('books', BookController::class);
});

