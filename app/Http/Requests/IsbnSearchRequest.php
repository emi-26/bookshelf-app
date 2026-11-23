<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IsbnSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => ['required', 'regex:/^[0-9]{13}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNは13桁で入力してください。',
            'isbn.regex' => 'ISBNは13桁で入力してください。',
        ];
    }

    public function validationData(): array
    {
        return [
            'isbn' => $this->route('isbn'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'error' => $validator->errors()->first('isbn'),
            ], 422)
        );
    }
}
