<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
   
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $user = $request->user();

        $courses = Course::with('teacher:id,name')
            ->when(
                
                ! ($user && $user->isAdmin()),
                function ($query) use ($user) {
                    if ($user && $user->isTeacher()) {
                       
                        $query->where(function ($q) use ($user) {
                            $q->where('teacher_id', $user->id)
                              ->orWhere('status', CourseStatus::Published);
                        });
                    }
                     else {
                        
                        $query->where('status', CourseStatus::Published);
                    }
                }
            )
            ->latest()
            ->paginate(15);

        return response()->json($courses);
    }

    
    public function show(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $course->load('teacher:id,name');

        return response()->json(['data' => $course]);
    }

    
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create(
            array_merge(
                $request->validated(),
                ['teacher_id' => $request->user()->id]
            )
        );

        $course->load('teacher:id,name');

        return response()->json(['data' => $course], 201);
    }

   
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course->update($request->validated());

        $course->load('teacher:id,name');

        return response()->json(['data' => $course]);
    }

    
    public function destroy(Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return response()->json(['message' => 'تم حذف الدورة بنجاح.'], 200);
    }
}
