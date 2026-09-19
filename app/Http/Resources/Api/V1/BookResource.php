<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'description' => $this->description,
            'published_date' => $this->published_date,
            'genres' => $this->genres ? $this->genres->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                ];
            }) : [],
            'avg_rating' => (float) round($this->reviews_avg_rating ?? ($this->relationLoaded('reviews') ? $this->reviews->avg('rating') : 0) ?? 0, 1),
            'reviews_count' => (int) ($this->reviews_count ?? ($this->relationLoaded('reviews') ? $this->reviews->count() : 0)),
        ];
    }
}