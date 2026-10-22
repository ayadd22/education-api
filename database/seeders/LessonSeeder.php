<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
   
    public function run(): void
    {
        $alice = User::where('email', 'alice@example.com')->first();
        $bob   = User::where('email', 'bob@example.com')->first();

     
        $lessonMap = [
            'Laravel for Beginners' => [
                ['title' => 'Introduction to MVC', 'description' => 'Understanding the Model-View-Controller pattern.'],
                ['title' => 'Routing Basics',       'description' => 'Defining and grouping routes.'],
                ['title' => 'Eloquent ORM',          'description' => 'Working with database models.'],
                ['title' => 'Blade Templates',       'description' => 'Building views with the Blade engine.'],
                ['title' => 'Authentication',        'description' => 'Laravel Breeze and Sanctum overview.'],
            ],
            'Advanced Laravel Techniques' => [
                ['title' => 'Service Container',  'description' => 'Dependency injection and binding.'],
                ['title' => 'Event System',       'description' => 'Dispatching and listening to events.'],
                ['title' => 'Queue Workers',      'description' => 'Processing jobs asynchronously.'],
                ['title' => 'Custom Artisan Commands', 'description' => 'Building CLI tools with Laravel.'],
            ],
            'Laravel APIs — Draft' => [
                ['title' => 'RESTful Design',     'description' => 'Principles of REST APIs.'],
                ['title' => 'API Resources',      'description' => 'Transforming Eloquent models for API responses.'],
                ['title' => 'Sanctum Tokens',     'description' => 'Token-based API authentication.'],
            ],
            'Vue.js Essentials' => [
                ['title' => 'Vue Instance & Directives', 'description' => 'Core concepts of Vue.js.'],
                ['title' => 'Component System',          'description' => 'Building reusable components.'],
                ['title' => 'Vue Router',                'description' => 'Client-side routing.'],
                ['title' => 'Pinia State Management',    'description' => 'Managing application state.'],
            ],
            'Full-Stack Development' => [
                ['title' => 'Project Setup',        'description' => 'Bootstrapping a Laravel + Vue.js project.'],
                ['title' => 'API Integration',      'description' => 'Connecting Vue.js to a Laravel API.'],
                ['title' => 'Auth Flow',            'description' => 'Implementing login with Sanctum and Vue Router guards.'],
                ['title' => 'Deployment Basics',    'description' => 'Deploying to a production server.'],
            ],
            'DevOps Fundamentals — Draft' => [
                ['title' => 'CI/CD Concepts',    'description' => 'Introduction to continuous integration.'],
                ['title' => 'GitHub Actions',    'description' => 'Automating workflows.'],
                ['title' => 'Docker Basics',     'description' => 'Containerising Laravel applications.'],
            ],
        ];

        foreach ($lessonMap as $courseTitle => $lessons) {
            $course = Course::where('title', $courseTitle)->first();

            if (! $course) {
                continue;
            }

            foreach ($lessons as $index => $lessonData) {
                Lesson::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'title'     => $lessonData['title'],
                    ],
                    [
                        'description' => $lessonData['description'],
                        'video_url'   => 'https://example.com/videos/' . \Illuminate\Support\Str::slug($lessonData['title']) . '.mp4',
                        'order'       => $index + 1,
                    ]
                );
            }
        }
    }
}
