<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. テスト対象ユーザーの取得（山田太郎・鈴木花子）
        $yamada = User::where('name', '山田太郎')->first() ?? User::first();
        $suzuki = User::where('name', '鈴木花子')->first() ?? User::skip(1)->first() ?? $yamada;

        // 2. 書籍データの取得（6件以上必要）
        $books = Book::all();
        if ($books->count() < 6) {
            $this->command->warn('書籍データが6件未満のため、ReadingPlanSeeder を正しく実行できません。');
            return;
        }

        $today = Carbon::today();

        // 3. テストデータの定義（重複防止のため各計画に異なる book_id を割り当て）
        $plansData = [
            // --- 山田太郎（主シナリオ 5件 / ID: 1〜5） ---
            [
                'user_id' => $yamada->id,
                'book_id' => $books[0]->id,
                'target_date' => $today->copy()->addDays(3),
                'status' => 'in_progress',
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[1]->id,
                'target_date' => $today->copy(),
                'status' => 'in_progress',
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[2]->id,
                'target_date' => $today->copy()->subDays(3),
                'status' => 'in_progress',
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[3]->id,
                'target_date' => $today->copy()->addDays(7),
                'status' => 'in_progress',
                'completed_at' => null,
            ],
            [
                'user_id' => $yamada->id,
                'book_id' => $books[4]->id,
                'target_date' => $today->copy()->subDays(10),
                'status' => 'completed',
                'completed_at' => $today->copy()->subDays(5),
            ],

            // --- 鈴木花子（認可テスト用 1件 / ID: 6） ---
            [
                'user_id' => $suzuki->id,
                'book_id' => $books[5]->id,
                'target_date' => $today->copy()->addDays(5),
                'status' => 'in_progress',
                'completed_at' => null,
            ],
        ];

        // 4. Eloquent の firstOrCreate を使って投入
        foreach ($plansData as $data) {
            ReadingPlan::firstOrCreate(
                [
                    'user_id' => $data['user_id'],
                    'book_id' => $data['book_id'],
                ],
                [
                    'target_date' => $data['target_date'],
                    'status' => $data['status'],
                    'completed_at' => $data['completed_at'],
                ]
            );
        }
    }
}