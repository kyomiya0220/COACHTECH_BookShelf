<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avg = round((float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating') ?? 0), 1);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date ? $this->published_date->format('Y-m-d') : null,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'genres' => $this->genre ? [
                [
                    'id' => $this->genre->id,
                    'name' => $this->genre->name,
                ]
            ] : [],
            'average_rating' => $avg,
            'avg_rating' => $avg, // ★キー名の互換性のため追加
            'review_count' => (int) ($this->reviews_count ?? $this->reviews()->count()),
            'reviews_count' => (int) ($this->reviews_count ?? $this->reviews()->count()), // ★キー名の互換性のため追加
            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'user_name' => $review->user->name ?? '匿名ユーザー',
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at?->toIso8601String(),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}