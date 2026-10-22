<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
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

    public function view(User $user, Assignment $assignment): bool
    {
        return true;
    }

   
    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->isTeacher()
            && $user->id === $assignment->course->teacher_id;
    }


    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->isTeacher()
            && $user->id === $assignment->course->teacher_id;
    }
}
