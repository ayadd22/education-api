<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
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
     * Any authenticated user may list lessons.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a single lesson.
     */
    public function view(User $user, Lesson $lesson): bool
    {
        return true;
    }

    /**
     * Only teachers may create lessons.
     *
     * NOTE: This policy method only checks that the user is a teacher.
     * The controller MUST additionally verify that the target course belongs
     * to the authenticated teacher before creating the lesson. This ownership
     * check cannot be done here because no Lesson instance exists yet at
     * creation time. Use $this->authorize('update', $course) or an explicit
     * ownership check in the controller/FormRequest before calling create().
     */
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Only the teacher who owns the parent course may update a lesson.
     * Ownership is resolved through the lesson's parent course.
     */
    public function update(User $user, Lesson $lesson): bool
    {
        return $user->isTeacher()
            && $user->id === $lesson->course->teacher_id;
    }

    /**
     * Only the teacher who owns the parent course may delete a lesson.
     * Ownership is resolved through the lesson's parent course.
     */
    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->isTeacher()
            && $user->id === $lesson->course->teacher_id;
    }
}
