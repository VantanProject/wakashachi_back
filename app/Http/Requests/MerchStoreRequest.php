<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MerchStoreRequest extends FormRequest
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
            'merch.translations' => 'required|array',
            'merch.translations.*.name' => 'required|string|max:255',
            'merch.translations.*.languageId' => 'required|integer',
            'merch.allergyIds' => 'required|array',
            'merch.allergyIds.*' => 'required|integer|exists:allergies,id',
            'merch.imgData' => 'required|file|image|max:10240',
            'merch.price' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'merch.translations.*.languageId.required' => '言語IDは必須です',
            'merch.translations.*.name.required' => '商品名は必須です',
            'merch.imgData.required' => '画像は必須です',
            'merch.imgData.file' => '画像はファイル形式でなければなりません',
            'merch.imgData.image' => '画像は画像形式でなければなりません',
            'merch.imgData.max' => '画像は10MB以下でなければなりません',
            'merch.price.required' => '価格は必須です',
            'merch.price.integer' => '価格は整数でなければなりません',
            'merch.price.min' => '価格は0以上でなければなりません',
            'merch.price.max' => '価格は1000000以下でなければなりません',
            'merch.price.numeric' => '価格は数値でなければなりません',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'messages' => collect($validator->errors()->messages())
                    ->flatten()
                    ->toArray()
            ], 422)
        );
    }
}
