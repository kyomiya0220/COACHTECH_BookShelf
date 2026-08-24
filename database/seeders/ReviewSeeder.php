<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        // 各書籍に割り当てるレビュー件数（計32件）
        $reviewCounts = [3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 2];

        $comments = [
            'とても素晴らしい内容でした。何度も読み返したい一冊です。',
            '考えさせられる内容が多く、非常に参考になりました。',
            '読みやすくてスムーズに読み進めることができました。おすすめです。',
            '期待通りの内容で、学びが多かったです。',
        ];

        foreach ($books as $index => $book) {
            $count = $reviewCounts[$index];
            $assignedUsers = $users->random($count);

            foreach ($assignedUsers as $user) {
                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => rand(3, 5),
                    'comment' => $comments[array_rand($comments)],
                ]);
            }
        }
    }
}
