<?php

namespace Tests\Feature;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Assignment;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createCourse(User $teacher, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'teacher_id'  => $teacher->id,
            'title'       => 'Test Course',
            'description' => 'Description.',
            'level'       => CourseLevel::Beginner->value,
            'status'      => CourseStatus::Published->value,
            'price'       => 0,
        ], $overrides));
    }

    private function createLesson(Course $course, array $overrides = []): Lesson
    {
        return Lesson::create(array_merge([
            'course_id' => $course->id,
            'title'     => 'Test Lesson',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'order'     => 1,
        ], $overrides));
    }

    private function createAssignment(Course $course, array $overrides = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id'   => $course->id,
            'title'       => 'Test Assignment',
            'description' => 'Test description.',
        ], $overrides));
    }

    private function enroll(User $student, Course $course): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id'  => $course->id,
            'progress'   => 0,
        ]);
    }

    private function createComment(User $user, $commentable, array $overrides = []): Comment
    {
        return $commentable->comments()->create(array_merge([
            'user_id' => $user->id,
            'body'    => 'A test comment.',
        ], $overrides));
    }

    // =========================================================================
    // 1. Unauthenticated requests return 401
    // =========================================================================

    public function test_unauthenticated_list_course_comments_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->getJson("/api/courses/{$course->id}/comments")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_create_course_comment_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->postJson("/api/courses/{$course->id}/comments", ['body' => 'Hello'])
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_list_lesson_comments_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}/comments")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_create_assignment_comment_returns_401(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->postJson("/api/courses/{$course->id}/assignments/{$assignment->id}/comments", ['body' => 'Hi'])
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_delete_comment_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $comment = $this->createComment($teacher, $course);

        $this->deleteJson("/api/comments/{$comment->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    // =========================================================================
    // 2. Enrolled student can comment on published course content
    // =========================================================================

    public function test_enrolled_student_can_comment_on_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->enroll($student, $course);

        $response = $this->actingAs($student)
                         ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Great course!']);

        $response->assertStatus(201)
                 ->assertJsonPath('data.body', 'Great course!')
                 ->assertJsonPath('data.user.id', $student->id);

        $this->assertDatabaseHas('comments', [
            'user_id'          => $student->id,
            'body'             => 'Great course!',
            'commentable_type' => 'course',
            'commentable_id'   => $course->id,
        ]);
    }

    public function test_enrolled_student_can_comment_on_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $lesson  = $this->createLesson($course);
        $this->enroll($student, $course);

        $response = $this->actingAs($student)
                         ->postJson("/api/courses/{$course->id}/lessons/{$lesson->id}/comments",
                             ['body' => 'Great lesson!']);

        $response->assertStatus(201)
                 ->assertJsonPath('data.body', 'Great lesson!');

        $this->assertDatabaseHas('comments', [
            'user_id'          => $student->id,
            'commentable_type' => 'lesson',
            'commentable_id'   => $lesson->id,
        ]);
    }

    public function test_enrolled_student_can_comment_on_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);
        $this->enroll($student, $course);

        $response = $this->actingAs($student)
                         ->postJson("/api/courses/{$course->id}/assignments/{$assignment->id}/comments",
                             ['body' => 'Good assignment!']);

        $response->assertStatus(201)
                 ->assertJsonPath('data.body', 'Good assignment!');

        $this->assertDatabaseHas('comments', [
            'user_id'          => $student->id,
            'commentable_type' => 'assignment',
            'commentable_id'   => $assignment->id,
        ]);
    }

    // =========================================================================
    // 3. Unenrolled student cannot comment
    // =========================================================================

    public function test_unenrolled_student_cannot_comment_on_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        // no enrollment

        $this->actingAs($student)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Hello'])
             ->assertStatus(403);
    }

    public function test_unenrolled_student_cannot_comment_on_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($student)
             ->postJson("/api/courses/{$course->id}/lessons/{$lesson->id}/comments", ['body' => 'Hi'])
             ->assertStatus(403);
    }

    public function test_unenrolled_student_cannot_comment_on_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($student)
             ->postJson("/api/courses/{$course->id}/assignments/{$assignment->id}/comments",
                 ['body' => 'Hi'])
             ->assertStatus(403);
    }

    // =========================================================================
    // 4. Student cannot comment on draft course content
    // =========================================================================

    public function test_student_cannot_comment_on_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        // Even if enrolled somehow, draft should be blocked
        $this->enroll($student, $course);

        $this->actingAs($student)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Sneaky'])
             ->assertStatus(403);
    }

    public function test_student_cannot_list_comments_on_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/comments")
             ->assertStatus(403);
    }

    // =========================================================================
    // 5. Teacher can comment on their own content (published and draft)
    // =========================================================================

    public function test_teacher_can_comment_on_own_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'My own course note.'])
             ->assertStatus(201)
             ->assertJsonPath('data.body', 'My own course note.');
    }

    public function test_teacher_can_comment_on_own_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Draft note.'])
             ->assertStatus(201);
    }

    public function test_teacher_can_comment_on_own_draft_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons/{$lesson->id}/comments",
                 ['body' => 'Draft lesson note.'])
             ->assertStatus(201);
    }

    // =========================================================================
    // 6. Teacher cannot comment on another teacher's draft content
    // =========================================================================

    public function test_teacher_cannot_comment_on_another_teachers_draft_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Draft->value]);

        $this->actingAs($other)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Sneaky comment'])
             ->assertStatus(403);
    }

    public function test_teacher_cannot_comment_on_another_teachers_draft_lesson(): void
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Draft->value]);
        $lesson = $this->createLesson($course);

        $this->actingAs($other)
             ->postJson("/api/courses/{$course->id}/lessons/{$lesson->id}/comments",
                 ['body' => 'Sneaky'])
             ->assertStatus(403);
    }

    public function test_teacher_can_comment_on_another_teachers_published_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);

        $this->actingAs($other)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Nice course!'])
             ->assertStatus(201);
    }

    // =========================================================================
    // 7. Listing comments returns author and is ordered latest-first
    // =========================================================================

    public function test_listing_course_comments_includes_author_and_body(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->enroll($student, $course);
        $this->createComment($student, $course, ['body' => 'First comment']);

        $response = $this->actingAs($teacher)
                         ->getJson("/api/courses/{$course->id}/comments");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'body', 'user' => ['id', 'name']]]])
                 ->assertJsonPath('data.0.body', 'First comment')
                 ->assertJsonPath('data.0.user.id', $student->id);
    }

    public function test_listing_lesson_comments_returns_200(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);
        $this->createComment($teacher, $lesson, ['body' => 'Lesson note']);

        $response = $this->actingAs($teacher)
                         ->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}/comments");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.body', 'Lesson note');
    }

    public function test_listing_assignment_comments_returns_200(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);
        $this->createComment($teacher, $assignment, ['body' => 'Assignment note']);

        $response = $this->actingAs($teacher)
                         ->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}/comments");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.body', 'Assignment note');
    }

    // =========================================================================
    // 8. Deleting comments
    // =========================================================================

    public function test_author_can_delete_own_comment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->enroll($student, $course);
        $comment = $this->createComment($student, $course);

        $this->actingAs($student)
             ->deleteJson("/api/comments/{$comment->id}")
             ->assertStatus(200)
             ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();
        $course   = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->enroll($studentA, $course);
        $this->enroll($studentB, $course);
        $comment = $this->createComment($studentA, $course);

        $this->actingAs($studentB)
             ->deleteJson("/api/comments/{$comment->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_teacher_cannot_delete_another_users_comment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->enroll($student, $course);
        $comment = $this->createComment($student, $course);

        // teacher is not the comment author
        $this->actingAs($teacher)
             ->deleteJson("/api/comments/{$comment->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    // =========================================================================
    // 9. Admin permissions
    // =========================================================================

    public function test_admin_can_list_comments_on_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createComment($teacher, $course, ['body' => 'Admin sees this']);

        $response = $this->actingAs($admin)
                         ->getJson("/api/courses/{$course->id}/comments");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.body', 'Admin sees this');
    }

    public function test_admin_can_comment_on_any_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $this->actingAs($admin)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => 'Admin note.'])
             ->assertStatus(201)
             ->assertJsonPath('data.user.id', $admin->id);
    }

    public function test_admin_can_delete_any_comment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->enroll($student, $course);
        $comment = $this->createComment($student, $course);

        $this->actingAs($admin)
             ->deleteJson("/api/comments/{$comment->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    // =========================================================================
    // 10. Validation errors return 422
    // =========================================================================

    public function test_store_returns_422_when_body_missing(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/comments", [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['body']);
    }

    public function test_store_returns_422_when_body_is_empty_string(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => ''])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['body']);
    }

    public function test_store_returns_422_when_body_exceeds_max_length(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/comments", ['body' => str_repeat('a', 2001)])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['body']);
    }

    // =========================================================================
    // 11. Mismatch: lesson/assignment not belonging to the course returns 404
    // =========================================================================

    public function test_comment_on_lesson_from_wrong_course_returns_404(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->createCourse($teacher);
        $courseB = $this->createCourse($teacher);
        $lessonB = $this->createLesson($courseB);

        // Try to list comments for lessonB through courseA's URL
        $this->actingAs($teacher)
             ->getJson("/api/courses/{$courseA->id}/lessons/{$lessonB->id}/comments")
             ->assertStatus(404);
    }

    public function test_comment_on_assignment_from_wrong_course_returns_404(): void
    {
        $teacher     = User::factory()->teacher()->create();
        $courseA     = $this->createCourse($teacher);
        $courseB     = $this->createCourse($teacher);
        $assignmentB = $this->createAssignment($courseB);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$courseA->id}/assignments/{$assignmentB->id}/comments",
                 ['body' => 'Wrong parent'])
             ->assertStatus(404);
    }

    // =========================================================================
    // 12. comment user_id is always from the authenticated user
    // =========================================================================

    public function test_store_ignores_client_supplied_user_id(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $impostor = User::factory()->teacher()->create();
        $course   = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        // Supply impostor's ID in the body — must be ignored
        $response = $this->actingAs($teacher)
                         ->postJson("/api/courses/{$course->id}/comments", [
                             'body'    => 'Legitimate comment.',
                             'user_id' => $impostor->id,
                         ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'id'      => $response->json('data.id'),
            'user_id' => $teacher->id,  // must be the authenticated user
        ]);
        $this->assertDatabaseMissing('comments', [
            'id'      => $response->json('data.id'),
            'user_id' => $impostor->id,
        ]);
    }
}
