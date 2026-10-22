<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Comment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommentPolicy
{
    /**
     * Admins bypass all policy checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user may create a comment on a given commentable.
     *
     * $commentable is the Course, Lesson, or Assignment being commented on.
     *
     * Rules:
     *   - Admins: allowed via before().
     *   - Teachers: may comment on published content OR their own course content
     *     (including drafts they own). For lessons/assignments, ownership is
     *     checked through the parent course.
     *   - Students: may comment only when enrolled in the parent course of the
     *     content. The parent course must be published.
     *
     * @param  User   $user         The authenticated user.
     * @param  Model  $commentable  The Course, Lesson, or Assignment model.
     */
    public function create(User $user, Model $commentable): bool
    {
        $course = $this->resolveCourse($commentable);

        if ($course === null) {
            return false;
        }

        if ($user->isTeacher()) {
            // Teacher may comment on their own content regardless of status,
            // or on any published course content.
            return $course->status === CourseStatus::Published
                || $user->id === $course->teacher_id;
        }

        if ($user->isStudent()) {
            // Student may comment only on published courses they are enrolled in.
            if ($course->status !== CourseStatus::Published) {
                return false;
            }

            return $user->enrollments()
                ->where('course_id', $course->id)
                ->exists();
        }

        return false;
    }

    /**
     * Only the comment author may delete their own comment.
     * Admins bypass via before().
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Resolve the parent Course regardless of whether the commentable is
     * a Course, Lesson, or Assignment.
     */
    private function resolveCourse(Model $commentable): ?Course
    {
        if ($commentable instanceof Course) {
            return $commentable;
        }

        // Lesson and Assignment both have a course() BelongsTo relationship.
        if (method_exists($commentable, 'course')) {
            return $commentable->course;
        }

        return null;
    }
}
