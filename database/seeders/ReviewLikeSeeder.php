<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        if ($users->isEmpty() || $reviews->isEmpty()) {
            return;
        }

        // 各レビューに対してランダムなユーザーからいいねを付ける
        foreach ($reviews as $review) {
            $randomUsers = $users->random(rand(1, min(3, $users->count())));

            foreach ($randomUsers as $user) {
                // user_id と review_id のみ指定して登録
                DB::table('review_likes')->insertOrIgnore([
                    'user_id' => $user->id,
                    'review_id' => $review->id,
                ]);
            }
        }
    }
}