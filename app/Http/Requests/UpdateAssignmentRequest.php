<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $this->user()->can('update', $assignment);
    }

    
    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'       => 'عنوان الواجب',
            'description' => 'وصف الواجب',
        ];
    }

    
    public function messages(): array
    {
        return [
            
            'title.required'       => 'عنوان الواجب مطلوب.',
            'title.string'         => 'يجب أن يكون عنوان الواجب نصًا.',
            'title.max'            => 'يجب ألا يتجاوز عنوان الواجب 255 حرفًا.',

         
            'description.required' => 'وصف الواجب مطلوب.',
            'description.string'   => 'يجب أن يكون وصف الواجب نصًا.',
        ];
    }
}