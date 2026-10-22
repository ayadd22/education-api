<?php

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
   
    public function run(): void
    {
        $alice = User::where('email', 'alice@example.com')->first();
        $bob   = User::where('email', 'bob@example.com')->first();

        $definitions = [
            // Alice's courses
            [
                'teacher'     => $alice,
                'title'       => 'Laravel for Beginners',
                'description' => 'A complete introduction to Laravel.',
                'level'       => CourseLevel::Beginner,
                'status'      => CourseStatus::Published,
                'price'       => 0.00,
            ],
            [
                'teacher'     => $alice,
                'title'       => 'Advanced Laravel Techniques',
                'description' => 'Deep dive into Laravel internals.',
                'level'       => CourseLevel::Advanced,
                'status'      => CourseStatus::Published,
                'price'       => 49.99,
            ],
            [
                'teacher'     => $alice,
                'title'       => 'Laravel APIs — Draft',
                'description' => 'Building APIs with Laravel (work in progress).',
                'level'       => CourseLevel::Intermediate,
                'status'      => CourseStatus::Draft,
                'price'       => 29.99,
            ],

            // Bob's courses
            [
                'teacher'     => $bob,
                'title'       => 'Vue.js Essentials',
                'description' => 'Frontend development with Vue.js.',
                'level'       => CourseLevel::Beginner,
                'status'      => CourseStatus::Published,
                'price'       => 19.99,
            ],
            [
                'teacher'     => $bob,
                'title'       => 'Full-Stack Development',
                'description' => 'Combining Laravel and Vue.js.',
                'level'       => CourseLevel::Intermediate,
                'status'      => CourseStatus::Published,
                'price'       => 79.99,
            ],
            [
                'teacher'     => $bob,
                'title'       => 'DevOps Fundamentals — Draft',
                'description' => 'CI/CD and deployment pipelines (work in progress).',
                'level'       => CourseLevel::Advanced,
                'status'      => CourseStatus::Draft,
                'price'       => 59.99,
            ],
        ];

        foreach ($definitions as $def) {
            Course::firstOrCreate(
                [
                    'teacher_id' => $def['teacher']->id,
                    'title'      => $def['title'],
                ],
                [
                    'description' => $def['description'],
                    'level'       => $def['level']->value,
                    'status'      => $def['status']->value,
                    'price'       => $def['price'],
                ]
            );
        }
    }
}
