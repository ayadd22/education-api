<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;

class LessonController extends Controller
{
   
    public function index(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $lessons = $course->lessons()
            ->orderBy('order')
            ->get();

        return response()->json(['data' => $lessons]);
    }

   
    public function show(Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfMismatched($course, $lesson);

        // Inherit course-level visibility: same rules as index.
        $this->authorize('view', $course);

        return response()->json(['data' => $lesson]);
    }

   
    public function store(StoreLessonRequest $request, Course $course): JsonResponse
    {
        $lesson = $course->lessons()->create(
            array_merge($request->validated(), ['course_id' => $course->id])
        );

        return response()->json(['data' => $lesson], 201);
    }

   
    public function update(UpdateLessonRequest $request, Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfMismatched($course, $lesson);

        $lesson->update($request->validated());

        return response()->json(['data' => $lesson->fresh()]);
    }

   
    public function destroy(Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfMismatched($course, $lesson);

        $this->authorize('delete', $lesson);

        $lesson->delete();

        return response()->json(['message' => 'تم حذف الدرس بنجاح.'], 200);
    }

    private function abortIfMismatched(Course $course, Lesson $lesson): void
    {
        if ($lesson->course_id !== $course->id) {
            abort(404, 'الدرس غير موجود في هذه الدورة.');
        }
    }
}
