<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = Review::all();

        foreach ($reviews as $review) {
            // 自分のレビューを除外したユーザーリスト
            $otherUsers = User::where('id', '!=', $review->user_id)->get();
            $likeCount = rand(0, min(3, $otherUsers->count()));

            if ($likeCount > 0) {
                $likers = $otherUsers->random($likeCount);
                $review->likes()->syncWithoutDetaching($likers->pluck('id'));
            }
        }
    }
}