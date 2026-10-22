<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
  
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

 
    public function create(User $user, Course $course): bool
    {
        return $user->isStudent()
            && $course->status === CourseStatus::Published;
    }

    /**
     * A student may only view their own enrollment records.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->id === $enrollment->student_id;
    }

    /**
     * A student may only delete (unenroll from) their own enrollment.
     */
    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->id === $enrollment->student_id;
    }
}
