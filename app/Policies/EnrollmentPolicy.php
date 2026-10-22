<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
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
     * Determine whether the user may create an enrollment.
     *
     * Rules enforced here:
     *   - Only students may enroll (teachers may not enroll as students).
     *   - The target course must be published.
     *   - A teacher may not enroll in their own course (belt-and-suspenders;
     *     the isTeacher() check above already blocks teachers entirely).
     *
     * Duplicate-enrollment prevention is handled at the controller layer
     * (DB unique constraint + explicit pre-check) rather than here, because
     * the Course model is needed to check it and policy::create() does not
     * receive a model instance.
     *
     * @param  User    $user    The authenticated user attempting to enroll.
     * @param  Course  $course  The course they wish to enroll in (passed as
     *                          the second argument to Gate::authorize).
     */
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
