<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;


class AssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            // course_id must be supplied by the caller or seeder.
            'course_id'   => Course::factory(),
            'title'       => $this->faker->sentence(4, true),
            'description' => $this->faker->paragraph(2),
        ];
    }

   
    public function forCourse(Course $course): static
    {
        return $this->state(['course_id' => $course->id]);
    }
}
