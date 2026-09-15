<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Genre extends Model
{
    use HasFactory;

    // 一括割り当てを許可するフィールド（必要に応じて変更してください）
    protected $fillable = ['name'];

    // Bookモデルとのリレーション（1対多の場合）
    public function books(): HasMany
    {
        return $this->HasMany(Book::class);
    }
}
