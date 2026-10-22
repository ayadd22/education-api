<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function latestComments(Request $request): JsonResponse
    {
        $teacherId = $request->user()->id;

        $comments = Comment::query()
           ->with([
              'user:id,name',
              'commentable',
                ])
            ->where(function (Builder $query) use ($teacherId): void {
                $query
                    ->whereHasMorph(
                        'commentable',
                        [Course::class],
                        fn (Builder $course) => $course->where('teacher_id', $teacherId)
                    )
                    ->orWhereHasMorph(
                        'commentable',
                        [Lesson::class],
                        fn (Builder $lesson) => $lesson->whereHas(
                            'course',
                            fn (Builder $course) => $course->where('teacher_id', $teacherId)
                        )
                    )
                    ->orWhereHasMorph(
                        'commentable',
                        [Assignment::class],
                        fn (Builder $assignment) => $assignment->whereHas(
                            'course',
                            fn (Builder $course) => $course->where('teacher_id', $teacherId)
                        )
                    );
            })
            ->latest()
            ->paginate(15);

        $comments->through(function (Comment $comment): array {
            $content = $comment->commentable;

            $contentType = match (true) {
                $content instanceof Course => 'course',
                $content instanceof Lesson => 'lesson',
                $content instanceof Assignment => 'assignment',
                default => 'unknown',
            };

            return [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ],
                'content_type' => $contentType,
                'content_id' => $content->id,
                'content_title' => $content->title,
                'created_at' => $comment->created_at,
            ];
        });

        return response()->json($comments);
    }
}
