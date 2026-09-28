<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreReadingPlanRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるか判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'exists:books,id',
                // ログイン中のユーザーが同じ book_id を重複登録するのを防ぐバリデーション
                Rule::unique('reading_plans')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                }),
            ],
            'target_date' => 'required|date|after_or_equal:today',
        ];
    }
    /**
     * カスタムバリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '正しい書籍を選択してください。',
            'book_id.unique' => 'この書籍はすでに読書計画に登録されています。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '正しい日付を入力してください。',
            'target_date.after_or_equal' => '期日には本日以降の日付を指定してください。',
        ];
    }
}