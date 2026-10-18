<?php

namespace App\Http\Requests;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCourseRequest extends FormRequest
{
 
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Course::class);
    }

    
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'level'       => ['required', new Enum(CourseLevel::class)],
            'price'       => ['required', 'numeric', 'min:0'],
            'status'      => ['sometimes', 'required', new Enum(CourseStatus::class)],
        ];
    }

   
    public function attributes(): array
    {
        return [
            'title'       => 'عنوان الدورة',
            'description' => 'وصف الدورة',
            'level'       => 'مستوى الدورة',
            'price'       => 'سعر الدورة',
            'status'      => 'حالة الدورة',
        ];
    }

   
    public function messages(): array
    {
        return [
        
            'title.required'     => 'عنوان الدورة مطلوب.',
            'title.string'       => 'يجب أن يكون عنوان الدورة نصًا.',
            'title.max'          => 'يجب ألا يتجاوز عنوان الدورة 255 حرفًا.',

         
            'description.string' => 'يجب أن يكون وصف الدورة نصًا.',

           
            'level.required'     => 'مستوى الدورة مطلوب.',
            'level.enum'         => 'مستوى الدورة يجب أن يكون: مبتدئ، متوسط، أو متقدم.',

            
            'price.required'     => 'سعر الدورة مطلوب.',
            'price.numeric'      => 'يجب أن يكون سعر الدورة رقمًا.',
            'price.min'          => 'يجب أن يكون سعر الدورة صفرًا أو أكثر.',

          
            'status.required'    => 'حالة الدورة مطلوبة عند تحديدها.',
            'status.enum'        => 'حالة الدورة يجب أن تكون: مسودة أو منشورة.',
        ];
    }
}
