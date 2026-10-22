<?php

namespace App\Services;

use App\Contracts\EnrollmentServiceInterface;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class EnrollmentService implements EnrollmentServiceInterface
{
  
    public function enroll(User $student, int $courseId): JsonResponse
    {
       
        $alreadyEnrolled = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->exists();

        if ($alreadyEnrolled) {
            return $this->duplicateResponse();
        }

       
        try {
            $enrollment = Enrollment::create([
                'student_id' => $student->id,
                'course_id'  => $courseId,
                'progress'   => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
           
            return $this->duplicateResponse();
        }

        $enrollment->load(['course:id,title,level,status,price']);

        return response()->json(['data' => $enrollment], 201);
    }

    
    private function duplicateResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'أنت مسجّل بالفعل في هذه الدورة.',
            'errors'  => ['course_id' => ['أنت مسجّل بالفعل في هذه الدورة.']],
        ], 422);
    }
}
