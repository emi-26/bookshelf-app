<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => [
                'nullable',
                Rule::in(['latest', 'oldest', 'title', 'rating']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => '並び替えの指定が正しくありません。',
        ];
    }
}
