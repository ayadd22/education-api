<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    
    public function authorize(): bool
    {
       
        if (! $this->user()->can('create', \App\Models\Lesson::class)) {
            return false;
        }

      
        $course = $this->route('course');

        return $this->user()->can('update', $course);
    }

  
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url'   => ['required', 'string', 'max:2048'],
            'order'       => ['required', 'integer', 'min:1'],
        ];
    }

   
    public function attributes(): array
    {
        return [
            'title'       => 'عنوان الدرس',
            'description' => 'وصف الدرس',
            'video_url'   => 'رابط الفيديو',
            'order'       => 'ترتيب الدرس',
        ];
    }

   
    public function messages(): array
    {
        return [
           
            'title.required'     => 'عنوان الدرس مطلوب.',
            'title.string'       => 'يجب أن يكون عنوان الدرس نصًا.',
            'title.max'          => 'يجب ألا يتجاوز عنوان الدرس 255 حرفًا.',

          
            'description.string' => 'يجب أن يكون وصف الدرس نصًا.',

          
            'video_url.required' => 'رابط الفيديو مطلوب.',
            'video_url.string'   => 'يجب أن يكون رابط الفيديو نصًا.',
            'video_url.max'      => 'يجب ألا يتجاوز رابط الفيديو 2048 حرفًا.',

          
            'order.required'     => 'ترتيب الدرس مطلوب.',
            'order.integer'      => 'يجب أن يكون ترتيب الدرس عددًا صحيحًا.',
            'order.min'          => 'يجب أن يكون ترتيب الدرس 1 على الأقل.',
        ];
    }
}
