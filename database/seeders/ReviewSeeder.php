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

        if ($users->isEmpty() || $books->isEmpty()) {
            return;
        }

        // 評価（1〜5）に応じた日本語コメントテンプレート（5段階）
        $commentsByRating = [
            1 => [
                '期待していた内容とは少し異なり、個人的には合いませんでした。',
                '文章が難解で、途中で読むのを諦めてしまいました。',
                'テーマは興味深かったですが、内容が薄く感じられました。',
            ],
            2 => [
                '悪くはないですが、もう少し具体的な説明があると良かったです。',
                '部分的には参考になりましたが、全体的には物足りない印象です。',
                'ターゲット層が合わなかったのか、あまり刺さりませんでした。',
            ],
            3 => [
                '可もなく不可もなく、標準的な内容の書籍でした。',
                '基礎的な知識をざっくりとおさらいするには良い本だと思います。',
                '一通り読み終えましたが、一般的な解説が多めでした。',
            ],
            4 => [
                '非常に分かりやすく、実務や日常で役立つ知見が得られました。',
                '文章のテンポが良く、最後まで楽しく読み進められました。',
                '内容が充実しており、手元に置いて何度も読み返したい一冊です。',
            ],
            5 => [
                '文句なしの名著です！知りたかった情報が完璧に網羅されていました。',
                '目から鱗の連続で、自分の価値観がガラリと変わる経験になりました。',
                'すべての人におすすめしたい、今年読んだ中で最高の書籍です。',
            ],
        ];

        foreach ($books as $book) {
            // 書籍ごとのレビュー件数をランダム化（rand(2, 4)）
            // ユーザー数を超えないように調整
            $reviewCount = rand(2, 4);
            $count = min($reviewCount, $users->count());

            // 投稿者をランダム選出（同一書籍への重複投稿防止）
            $selectedUsers = $users->random($count);

            foreach ($selectedUsers as $user) {
                // 評価範囲を 1〜5 に拡大
                $rating = rand(1, 5);

                // 評価に応じたコメントテンプレートからランダム選択
                $comments = $commentsByRating[$rating];
                $comment = $comments[array_rand($comments)];

                // レビューデータの作成（重複防止で firstOrCreate）
                Review::firstOrCreate(
                    [
                        'book_id' => $book->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'rating' => $rating,
                        'comment' => $comment,
                    ]
                );
            }
        }
    }
}
