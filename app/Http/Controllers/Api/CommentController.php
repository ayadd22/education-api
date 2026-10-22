<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
   
    public function indexForCourse(Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $comments = $course->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json(['data' => $comments]);
    }

    
    public function storeForCourse(StoreCommentRequest $request, Course $course): JsonResponse
    {
        return $this->storeComment($request, $course);
    }

  
    public function indexForLesson(Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfLessonMismatched($course, $lesson);
        $this->authorize('view', $course);

        $comments = $lesson->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json(['data' => $comments]);
    }

   
    public function storeForLesson(StoreCommentRequest $request, Course $course, Lesson $lesson): JsonResponse
    {
        $this->abortIfLessonMismatched($course, $lesson);
        return $this->storeComment($request, $lesson);
    }

   
    public function indexForAssignment(Course $course, Assignment $assignment): JsonResponse
    {
        $this->abortIfAssignmentMismatched($course, $assignment);
        $this->authorize('view', $course);

        $comments = $assignment->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json(['data' => $comments]);
    }

    
    public function storeForAssignment(StoreCommentRequest $request, Course $course, Assignment $assignment): JsonResponse
    {
        $this->abortIfAssignmentMismatched($course, $assignment);
        return $this->storeComment($request, $assignment);
    }

  
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'تم حذف التعليق بنجاح.'], 200);
    }

    private function storeComment(StoreCommentRequest $request, Model $commentable): JsonResponse
    {
        $this->authorize('create', [Comment::class, $commentable]);

        $comment = $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->validated()['body'],
        ]);

        $comment->load('user:id,name');

        return response()->json(['data' => $comment], 201);
    }

    private function abortIfLessonMismatched(Course $course, Lesson $lesson): void
    {
        if ($lesson->course_id !== $course->id) {
            abort(404, 'الدرس غير موجود في هذه الدورة.');
        }
    }

    private function abortIfAssignmentMismatched(Course $course, Assignment $assignment): void
    {
        if ($assignment->course_id !== $course->id) {
            abort(404, 'الواجب غير موجود في هذه الدورة.');
        }
    }
}
