<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MenustoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'menu.name' => 'required|string|max:255',
            'menu.color' => 'required|string|max:50',
            'menu.pages' => 'required|array',
            'menu.pages.*.count' => 'required|integer|min:1',
            'menu.pages.*.items' => 'required|array',
            'menu.pages.*.items.*.type' => 'required|string|in:merch,text',
            'menu.pages.*.items.*.width' => 'required|integer|min:1',
            'menu.pages.*.items.*.height' => 'required|integer|min:1',
            'menu.pages.*.items.*.top' => 'required|integer|min:0',
            'menu.pages.*.items.*.left' => 'required|integer|min:0',
            'menu.pages.*.items.*.merchId' => 'required_if:menu.pages.*.items.*.type,merch|integer',
            'menu.pages.*.items.*.color' => 'required_if:menu.pages.*.items.*.type,text|string|max:50',
            'menu.pages.*.items.*.translations' => 'required_if:menu.pages.*.items.*.type,text|array',
            'menu.pages.*.items.*.translations.*.languageId' => 'required|integer',
            'menu.pages.*.items.*.translations.*.text' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'menu.name.required' => 'メニュー名は必須です。',
            'menu.name.string' => 'メニュー名は文字列でなければなりません。',
            'menu.color.required' => 'メニューの色は必須です。',
            'menu.color.string' => 'メニューの色は文字列でなければなりません。',
            'menu.pages.*.items.*.type.required' => 'アイテムのタイプは必須です。',
            'menu.pages.*.items.*.type.in' => 'アイテムのタイプは "merch" または "text" のいずれかである必要があります。',
            'menu.pages.*.items.*.width.integer' => 'アイテムの幅は整数でなければなりません。',
            'menu.pages.*.items.*.height.integer' => 'アイテムの高さは整数でなければなりません。',
            'menu.pages.*.items.*.height.min' => 'アイテムの高さは1以上である必要があります。',
            'menu.pages.*.items.*.top.integer' => 'アイテムの上辺位置は整数でなければなりません。',
            'menu.pages.*.items.*.left.integer' => 'アイテムの左辺位置は整数でなければなりません。',
            'menu.pages.*.items.*.merchId.required_if' => 'merchアイテムにはmerchIdが必要です。',
            'menu.pages.*.items.*.color.required_if' => 'textアイテムには色が必要です。',
            'menu.pages.*.items.*.color.string' => '色は文字列でなければなりません。',
            'menu.pages.*.items.*.translations.required_if' => 'textアイテムには翻訳が必要です。',
            'menu.pages.*.items.*.translations.*.languageId.required' => '翻訳にはlanguageIdが必要です。',
            'menu.pages.*.items.*.translations.*.languageId.exists' => '指定されたlanguageIdが存在しません。',
            'menu.pages.*.items.*.translations.*.text.required' => '翻訳のテキストは必須です。',
            'menu.pages.*.items.*.translations.*.text.string' => '翻訳のテキストは文字列でなければなりません。',
        ];
    }
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors' => collect($validator->errors()->messages())
                    ->flatten()
                    ->toArray()
            ], 422)
        );
    }
}
