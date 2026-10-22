<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
          
            'student_id' => User::factory(),
            'course_id'  => Course::factory()->published(),
            'progress'   => $this->faker->numberBetween(0, 100),
        ];
    }

    
    public function forStudentAndCourse(User $student, Course $course): static
    {
        return $this->state([
            'student_id' => $student->id,
            'course_id'  => $course->id,
        ]);
    }
}
