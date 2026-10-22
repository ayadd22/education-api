<?php

namespace App\Contracts;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;

interface EnrollmentServiceInterface
{
    
    public function enroll(User $student, int $courseId): JsonResponse;
}
