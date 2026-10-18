<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
   
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

  
    public function viewAny(User $user): bool
    {
        return true;
    }

  
    public function view(User $user, Course $course): bool
    {
        
        if ($course->status === CourseStatus::Published) {
            return true;
        }

        return $user->isTeacher() && $user->id === $course->teacher_id;
    }

 
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

   
    public function update(User $user, Course $course): bool
    {
        return $user->isTeacher() && $user->id === $course->teacher_id;
    }

    
    public function delete(User $user, Course $course): bool
    {
        return $user->isTeacher() && $user->id === $course->teacher_id;
    }
}
