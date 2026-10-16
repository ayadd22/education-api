<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
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
     * Any authenticated user may list assignments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a single assignment.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        return true;
    }

    /**
     * Only teachers may create assignments.
     *
     * NOTE: This policy method only checks that the user is a teacher.
     * The controller MUST additionally verify that the target course belongs
     * to the authenticated teacher before creating the assignment. This ownership
     * check cannot be done here because no Assignment instance exists yet at
     * creation time. Use $this->authorize('update', $course) or an explicit
     * ownership check in the controller/FormRequest before calling create().
     */
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    /**
     * Only the teacher who owns the parent course may update an assignment.
     * Ownership is resolved through the assignment's parent course.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isTeacher()
            && $user->id === $assignment->course->teacher_id;
    }

    /**
     * Only the teacher who owns the parent course may delete an assignment.
     * Ownership is resolved through the assignment's parent course.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->isTeacher()
            && $user->id === $assignment->course->teacher_id;
    }
}
