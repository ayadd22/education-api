<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;


class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
           
            'course_id'   => Course::factory(),
            'title'       => $this->faker->sentence(5, true),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'video_url'   => 'https://example.com/videos/' . $this->faker->uuid() . '.mp4',
            'order'       => 1,
        ];
    }

    
    public function forCourse(Course $course, int $order): static
    {
        return $this->state([
            'course_id' => $course->id,
            'order'     => $order,
        ]);
    }
}
