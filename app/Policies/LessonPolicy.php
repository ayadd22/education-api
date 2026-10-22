<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
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

   
    public function view(User $user, Lesson $lesson): bool
    {
        return true;
    }

  
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    
    public function update(User $user, Lesson $lesson): bool
    {
        return $user->isTeacher()
            && $user->id === $lesson->course->teacher_id;
    }

    
    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->isTeacher()
            && $user->id === $lesson->course->teacher_id;
    }
}
