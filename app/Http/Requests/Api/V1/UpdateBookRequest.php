<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ポリシー認可は Controller 側で実行するため true
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'author' => ['sometimes', 'required', 'string', 'max:255'],
            'isbn' => [
                'sometimes',
                'required',
                'string',
                'regex:/^\d{13}$/',
                Rule::unique('books', 'isbn')->ignore($this->route('book')->id),
            ],
            'published_date' => ['sometimes', 'required', 'date', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:1000'],
            'genre_id' => ['sometimes', 'required', 'integer', 'exists:genres,id'],
        ];
    }
}