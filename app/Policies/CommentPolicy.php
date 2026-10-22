<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Comment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CommentPolicy
{
    
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

  
    public function create(User $user, Model $commentable): bool
    {
        $course = $this->resolveCourse($commentable);

        if ($course === null) {
            return false;
        }

        if ($user->isTeacher()) {
           
            return $course->status === CourseStatus::Published
                || $user->id === $course->teacher_id;
        }

        if ($user->isStudent()) {
            
            if ($course->status !== CourseStatus::Published) {
                return false;
            }

            return $user->enrollments()
                ->where('course_id', $course->id)
                ->exists();
        }

        return false;
    }

    
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    
    private function resolveCourse(Model $commentable): ?Course
    {
        if ($commentable instanceof Course) {
            return $commentable;
        }

       
        if (method_exists($commentable, 'course')) {
            return $commentable->course;
        }

        return null;
    }
}
