<?php

namespace App\Http\Requests;

use App\Http\Requests\UpdateGenreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ルーティングパラメータから現在対象の Genre モデルを取得
        $genreId = $this->route('genre')?->id ?? $this->route('genre');

        return [
            'name' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('genres', 'name')->ignore($genreId), // ★自分自身のIDを除外
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名は必須です。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'ジャンル名は既に使用されています。',
        ];
    }
}