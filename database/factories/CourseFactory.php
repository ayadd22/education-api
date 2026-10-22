<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
        
            'teacher_id'  => User::factory()->teacher(),
            'title'       => $this->faker->sentence(4, true),
            'description' => $this->faker->paragraph(2),
            'level'       => $this->faker->randomElement(CourseLevel::cases())->value,
            'status'      => CourseStatus::Published->value,
            'price'       => $this->faker->randomElement([0, 9.99, 19.99, 49.99, 99.99]),
        ];
    }

  
    public function draft(): static
    {
        return $this->state(['status' => CourseStatus::Draft->value]);
    }

  
    public function published(): static
    {
        return $this->state(['status' => CourseStatus::Published->value]);
    }


    public function forTeacher(User $teacher): static
    {
        return $this->state(['teacher_id' => $teacher->id]);
    }
}
