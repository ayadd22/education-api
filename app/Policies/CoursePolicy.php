<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Admins bypass all policy checks via before().
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Any authenticated user may list courses.
     * Filtering to published-only for students is handled at the query layer.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a single course.
     */
    public function view(User $user, Course $course): bool
    {
        return true;
    }

    /**
     * Only teachers may create courses.
     */
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Only the teacher who owns this course may update it.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->isTeacher() && $user->id === $course->teacher_id;
    }

    /**
     * Only the teacher who owns this course may delete it.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->isTeacher() && $user->id === $course->teacher_id;
    }
}
