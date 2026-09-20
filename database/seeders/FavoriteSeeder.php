<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        if ($users->isEmpty() || $books->isEmpty()) {
            return;
        }

        // 各ユーザーがランダムにいくつかの本をお気に入りに登録
        foreach ($users as $user) {
            $randomBooks = $books->random(rand(1, min(3, $books->count())));

            foreach ($randomBooks as $book) {
                // HasMany の場合：firstOrCreate を使用して重複登録を防止
                $user->favorites()->firstOrCreate([
                    'book_id' => $book->id,
                ]);
            }
        }
    }
}