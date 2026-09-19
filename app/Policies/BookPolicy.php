<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 書籍の更新権限チェック（本人かどうか）
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 書籍の削除権限チェック（本人かどうか）
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}