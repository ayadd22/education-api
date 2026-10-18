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
    /**
     * GET /api/courses/{course}/lessons
     *
     * List all lessons for a course.
     *
     * Visibility is inherited from the parent course:
     *   - CoursePolicy::view() already enforces that students cannot see
     *     draft courses, teachers can only see their own drafts, and admins
     *     see everything. We reuse that check here so lesson visibility
     *     always mirrors course visibility without duplicating the logic.
     */
    public function index(Course $course): JsonResponse
    {
        // Gate on the parent course — if the user cannot view the course
        // they cannot see its lessons either.
        $this->authorize('view', $course);

        $lessons = $course->lessons()
            ->orderBy('order')
            ->get();

        return response()->json(['data' => $lessons]);
    }

    /**
     * GET /api/courses/{course}/lessons/{lesson}
     *
     * Show a single lesson.
     *
     * A 404 is returned when the lesson does not belong to the course in the
     * URL, preventing cross-course ID enumeration.
     */
    public function show(Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfMismatched($course, $lesson);

        // Inherit course-level visibility: same rules as index.
        $this->authorize('view', $course);

        return response()->json(['data' => $lesson]);
    }

    /**
     * POST /api/courses/{course}/lessons
     *
     * Create a lesson under the given course.
     *
     * Authorization is handled entirely by StoreLessonRequest::authorize():
     *   - Step 1: user must be a teacher or admin (LessonPolicy::create).
     *   - Step 2: teacher must own the parent course (CoursePolicy::update).
     * course_id is taken from the route-bound model, never from client input.
     */
    public function store(StoreLessonRequest $request, Course $course): JsonResponse
    {
        $lesson = $course->lessons()->create(
            array_merge($request->validated(), ['course_id' => $course->id])
        );

        return response()->json(['data' => $lesson], 201);
    }

    /**
     * PUT/PATCH /api/courses/{course}/lessons/{lesson}
     *
     * Update a lesson.
     *
     * Authorization is handled by UpdateLessonRequest::authorize():
     *   - LessonPolicy::update() checks isTeacher() && $user->id === $lesson->course->teacher_id.
     *   - Admins bypass via before().
     * A 404 is returned for a mismatched course/lesson pair.
     */
    public function update(UpdateLessonRequest $request, Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfMismatched($course, $lesson);

        $lesson->update($request->validated());

        return response()->json(['data' => $lesson->fresh()]);
    }

    /**
     * DELETE /api/courses/{course}/lessons/{lesson}
     *
     * Delete a lesson.
     *
     * Authorization via LessonPolicy::delete(): owning teacher or admin only.
     * A 404 is returned for a mismatched course/lesson pair.
     */
    public function destroy(Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfMismatched($course, $lesson);

        $this->authorize('delete', $lesson);

        $lesson->delete();

        return response()->json(['message' => 'تم حذف الدرس بنجاح.'], 200);
    }

    /**
     * Abort with 404 if the lesson does not belong to the given course.
     *
     * Laravel's nested apiResource does NOT automatically scope the {lesson}
     * binding to the {course} — it resolves each parameter independently.
     * This check prevents accessing a valid lesson through a mismatched course URL.
     */
    private function abortIfMismatched(Course $course, Lesson $lesson): void
    {
        if ($lesson->course_id !== $course->id) {
            abort(404, 'الدرس غير موجود في هذه الدورة.');
        }
    }
}
