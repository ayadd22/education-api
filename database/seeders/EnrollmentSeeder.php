<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
   
    public function run(): void
    {
        
        $carol = User::where('email', 'carol@example.com')->first();
        $dave  = User::where('email', 'dave@example.com')->first();
        $eve   = User::where('email', 'eve@example.com')->first();

        if (! $carol || ! $dave || ! $eve) {
            $this->command->warn('EnrollmentSeeder: named demo students not found. Run UserSeeder first.');
            return;
        }

        $publishedCourses = Course::where('status', CourseStatus::Published->value)
            ->whereIn('title', [
                'Laravel for Beginners',
                'Advanced Laravel Techniques',
                'Vue.js Essentials',
                'Full-Stack Development',
            ])
            ->get();

        
        $matrix = [
            $carol->id => ['Laravel for Beginners', 'Vue.js Essentials'],
            $dave->id  => ['Laravel for Beginners', 'Advanced Laravel Techniques', 'Full-Stack Development'],
            $eve->id   => ['Vue.js Essentials', 'Full-Stack Development'],
        ];

        foreach ($matrix as $studentId => $courseTitles) {
            foreach ($courseTitles as $title) {
                $course = $publishedCourses->firstWhere('title', $title);

                if (! $course) {
                    continue;
                }

                Enrollment::firstOrCreate(
                    [
                        'student_id' => $studentId,
                        'course_id'  => $course->id,
                    ],
                    [
                        'progress' => 0,
                    ]
                );
            }
        }

       
        $laravelCourse = Course::where('title', 'Laravel for Beginners')->first();

        if ($laravelCourse) {
            $randomStudents = User::where('role', 'student')
                ->whereNotIn('email', ['carol@example.com', 'dave@example.com', 'eve@example.com'])
                ->limit(5)
                ->get();

            foreach ($randomStudents as $student) {
                Enrollment::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id'  => $laravelCourse->id,
                    ],
                    [
                        'progress' => fake()->numberBetween(0, 100),
                    ]
                );
            }
        }
    }
}
