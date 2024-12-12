<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMerchRequest extends FormRequest
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
            'merch.items' => 'required|array',
            'merch.items.*.name' => 'required|string|max:255',
            'merch.items.*.detail' => 'required|string|max:255',
            'merch.items.*.language_id' => 'required|integer|exists:languages,id',
            'merch.allergyIds' => 'required|array',
            'merch.allergyIds.*' => 'required|integer|exists:allergies,id',
            'merch.img_data' => 'required|file|image|max:10240',
            'merch.price' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'merch.items.*.name.required' => '商品名は必須です',
            'merch.items.*.detail.required' => '商品詳細は必須です',
            'merch.items.*.detail.max' => '商品詳細は255文字以下でなければなりません',
            'merch.img_data.required' => '画像は必須です',
            'merch.img_data.file' => '画像はファイル形式でなければなりません',
            'merch.img_data.image' => '画像は画像形式でなければなりません',
            'merch.img_data.max' => '画像は10MB以下でなければなりません',
            'merch.price.required' => '価格は必須です',
            'merch.price.integer' => '価格は整数でなければなりません',
            'merch.price.min' => '価格は0以上でなければなりません',
            'merch.price.max' => '価格は1000000以下でなければなりません',
            'merch.price.numeric' => '価格は数値でなければなりません',
        ];
    }
}
