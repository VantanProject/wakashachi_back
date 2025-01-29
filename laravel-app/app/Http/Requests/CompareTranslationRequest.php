<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompareTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => 'required|string',
            'sourceId' => 'required|string|in:1,2,3,4',
            'targetId' => 'required|string|in:1,2,3,4'
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'テキストは必須です',
            'sourceId.required' => '原言語の指定は必須です',
            'targetId.required' => '翻訳先言語の指定は必須です',
            'sourceId.in' => '対応していない言語です',
            'targetId.in' => '対応していない言語です'
        ];
    }
}
