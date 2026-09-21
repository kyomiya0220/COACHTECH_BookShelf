<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
            'average_rating' => round((float) ($this->reviews_avg_rating ?? $this->reviews()->avg('rating') ?? 0), 1),
            'review_count' => (int) ($this->reviews_count ?? $this->reviews()->count()),

            // 詳細表示時（reviewsリレーションがロードされている場合のみ）含める
            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'user_name' => $review->user->name ?? '匿名ユーザー',
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at->toIso8601String(),
                    ];
                });
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}