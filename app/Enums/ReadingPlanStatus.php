<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Reading = 'reading';
    case Completed = 'completed';
    case Overdue = 'overdue';

    /**
     * ラベルテキストを返す
     */
    public function label(): string
    {
        return match ($this) {
            self::Reading => '読書中',
            self::Completed => '完了',
            self::Overdue => '期限切れ',
        };
    }

    /**
     * バッジ用クラス文字列を返す
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Reading => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Overdue => 'bg-red-100 text-red-800',
        };
    }
}
