<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // URLパラメータから編集中の Book オブジェクトを取得（新規時は null）
        $book = $this->route('book');
        $bookId = $book ? $book->id : null; {
            return [
                'title' => ['required', 'string', 'max:255'],
                'author' => ['required', 'string', 'max:255'],
                'isbn' => [
                    'required',
                    'regex:/^\d{13}$/',
                    'unique:books,isbn',
                ],
                'published_date' => ['required', 'date', 'date_format:Y-m-d'],
                'description' => ['nullable', 'string', 'max:1000'],
                'image_url' => ['nullable', 'url', 'max:2048'],
                'genres' => ['required', 'array', 'min:1'],
                'genres.*' => ['integer', 'exists:genres,id'],
            ];
        }
    }
    public function messages(): array
    {
        return [
            'isbn.regex' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名を入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'isbn.required' => 'ISBNを入力してください。',
            'isbn.regex' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => 'このISBNは既に使用されています。',
            'published_date.required' => '出版日を入力してください。',
            'published_date.date_format' => '出版日は有効な日付形式で入力してください。',
            'description.max' => '説明は1000文字以内で入力してください。',
            'image_url.url' => '画像URLは有効なURL形式で入力してください。',
            'genres.required' => 'ジャンルを選択してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.integer' => '選択されたジャンルが不正です。',
        ];
    }
}

