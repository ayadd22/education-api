<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
   
    public function index(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $assignments = $course->assignments()
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $assignments]);
    }

   
    public function show(Course $course, Assignment $assignment): JsonResponse
    {
        $this->abortIfMismatched($course, $assignment);

        $this->authorize('view', $course);

        return response()->json(['data' => $assignment]);
    }

    public function store(StoreAssignmentRequest $request, Course $course): JsonResponse
    {
        $assignment = $course->assignments()->create(
            array_merge($request->validated(), ['course_id' => $course->id])
        );

        return response()->json(['data' => $assignment], 201);
    }

   
    public function update(UpdateAssignmentRequest $request, Course $course, Assignment $assignment): JsonResponse
    {
        $this->abortIfMismatched($course, $assignment);

        $assignment->update($request->validated());

        return response()->json(['data' => $assignment->fresh()]);
    }

   
    public function destroy(Course $course, Assignment $assignment): JsonResponse
    {
        $this->abortIfMismatched($course, $assignment);

        $this->authorize('delete', $assignment);

        $assignment->delete();

        return response()->json(['message' => 'تم حذف الواجب بنجاح.'], 200);
    }

   
    private function abortIfMismatched(Course $course, Assignment $assignment): void
    {
        if ($assignment->course_id !== $course->id) {
            abort(404, 'الواجب غير موجود في هذه الدورة.');
        }
    }
}
