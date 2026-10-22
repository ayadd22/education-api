<?php

namespace App\Providers;

use App\Contracts\EnrollmentServiceInterface;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Policies\AssignmentPolicy;
use App\Policies\CommentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\LessonPolicy;
use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            EnrollmentServiceInterface::class,
            EnrollmentService::class,
        );
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'course'                => Course::class,
            'lesson'                => Lesson::class,
            'assignment'            => Assignment::class,
            'comment'               => Comment::class,
            'user'                  => User::class,
            'personal_access_token' => PersonalAccessToken::class,
        ]);

        Gate::policy(Course::class,      CoursePolicy::class);
        Gate::policy(Lesson::class,      LessonPolicy::class);
        Gate::policy(Assignment::class,  AssignmentPolicy::class);
        Gate::policy(Enrollment::class,  EnrollmentPolicy::class);
        Gate::policy(Comment::class,     CommentPolicy::class);
    }
}
