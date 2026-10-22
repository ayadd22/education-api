<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Database\Seeder;

class AssignmentSeeder extends Seeder
{
   
    public function run(): void
    {
        $assignmentMap = [
            'Laravel for Beginners' => [
                [
                    'title'       => 'Build a Basic CRUD Application',
                    'description' => 'Create a simple blog application using Laravel with full Create, Read, Update, and Delete functionality.',
                ],
                [
                    'title'       => 'Implement Authentication',
                    'description' => 'Add user registration and login to your blog application using Laravel Sanctum.',
                ],
            ],
            'Advanced Laravel Techniques' => [
                [
                    'title'       => 'Design a Service Layer',
                    'description' => 'Refactor a controller-heavy application by extracting business logic into dedicated service classes.',
                ],
                [
                    'title'       => 'Build a Queue-Based Notification System',
                    'description' => 'Implement email notifications using Laravel queues and a mail driver of your choice.',
                ],
            ],
            'Laravel APIs — Draft' => [
                [
                    'title'       => 'Design a RESTful API',
                    'description' => 'Define and document a RESTful API for a simple resource, including authentication.',
                ],
            ],
            'Vue.js Essentials' => [
                [
                    'title'       => 'Build a To-Do List App',
                    'description' => 'Create a reactive to-do list using Vue.js components, local state, and event handling.',
                ],
            ],
            'Full-Stack Development' => [
                [
                    'title'       => 'Full-Stack Feature Implementation',
                    'description' => 'Add a complete feature (backend API + Vue.js frontend) to an existing project, including authentication guards.',
                ],
                [
                    'title'       => 'Deploy to Production',
                    'description' => 'Deploy your full-stack application to a cloud provider and document the deployment process.',
                ],
            ],
            'DevOps Fundamentals — Draft' => [
                [
                    'title'       => 'Set Up a CI/CD Pipeline',
                    'description' => 'Configure a GitHub Actions workflow that runs tests and deploys to a staging environment on every push to main.',
                ],
            ],
        ];

        foreach ($assignmentMap as $courseTitle => $assignments) {
            $course = Course::where('title', $courseTitle)->first();

            if (! $course) {
                continue;
            }

            foreach ($assignments as $assignmentData) {
                Assignment::firstOrCreate(
                    [
                        'course_id' => $course->id,
                        'title'     => $assignmentData['title'],
                    ],
                    [
                        'description' => $assignmentData['description'],
                    ]
                );
            }
        }
    }
}
