<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Policies\AssignmentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
       
        Relation::enforceMorphMap([
            'course'     => Course::class,
            'lesson'     => Lesson::class,
            'assignment' => Assignment::class,
        ]);

       
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
    }
}
