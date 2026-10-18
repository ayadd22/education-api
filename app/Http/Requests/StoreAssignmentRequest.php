<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
   
    public function authorize(): bool
    {
       
        if (! $this->user()->can('create', \App\Models\Assignment::class)) {
            return false;
        }

    
        $course = $this->route('course');

        return $this->user()->can('update', $course);
    }

  
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
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
