<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'نص التعليق',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'نص التعليق مطلوب.',
            'body.string'   => 'يجب أن يكون نص التعليق نصًا.',
            'body.max'      => 'يجب ألا يتجاوز نص التعليق 2000 حرف.',
        ];
    }
}
