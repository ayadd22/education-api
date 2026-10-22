<?php

namespace App\Http\Controllers\Api;

use App\Contracts\EnrollmentServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentServiceInterface $enrollmentService,
    ) {}

  
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = Enrollment::with(['course:id,title,level,status,price', 'student:id,name'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('student_id', $user->id))
            ->latest()
            ->get();

        return response()->json(['data' => $enrollments]);
    }

    
    public function store(StoreEnrollmentRequest $request): JsonResponse
    {
        return $this->enrollmentService->enroll(
            $request->user(),
            $request->validated()['course_id'],
        );
    }

    public function show(Enrollment $enrollment): JsonResponse
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['course:id,title,level,status,price', 'student:id,name']);

        return response()->json(['data' => $enrollment]);
    }

   
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $this->authorize('delete', $enrollment);

        $enrollment->delete();

        return response()->json(['message' => 'تم إلغاء التسجيل بنجاح.'], 200);
    }
}
