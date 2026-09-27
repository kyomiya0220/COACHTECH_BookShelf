<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ログインユーザー本人のデータか認可チェック（Policyを使用）
        $readingPlan = $this->route('reading_plan');
        return $this->user()->can('update', $readingPlan);
    }

    public function rules(): array
    {
        return [
            'target_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '正しい日付を入力してください。',
        ];
    }
}
