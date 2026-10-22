<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        $course = Course::find($this->input('course_id'));

        // No course found — defer to validation (rules()) which will produce 422.
        if (! $course) {
            return true;
        }

        return $this->user()->can('create', [
            \App\Models\Enrollment::class,
            $course,
        ]);
    }

  
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ];
    }

  
    public function attributes(): array
    {
        return [
            'course_id' => 'الدورة',
        ];
    }

   
    public function messages(): array
    {
        return [
            'course_id.required' => 'يجب تحديد الدورة المراد الالتحاق بها.',
            'course_id.integer'  => 'معرّف الدورة يجب أن يكون رقمًا صحيحًا.',
            'course_id.exists'   => 'الدورة المحددة غير موجودة.',
        ];
    }
}
