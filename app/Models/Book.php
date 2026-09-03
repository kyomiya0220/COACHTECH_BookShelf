<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Book extends Model
{
    use HasFactory;

    // 許可するカラム名（必要に応じて変更してください）
    protected $fillable = ['title', 'genre_id'];

    // Genreモデルとのリレーション（Bookは1つのGenreに属する）
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

}