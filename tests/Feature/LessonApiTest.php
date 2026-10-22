<?php

namespace Tests\Feature;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers — direct DB creation (no factories for Course/Lesson yet)
    // -------------------------------------------------------------------------

    private function createCourse(User $teacher, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'teacher_id'  => $teacher->id,
            'title'       => 'Test Course',
            'description' => 'A test description.',
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

    private function validLessonPayload(array $overrides = []): array
    {
        return array_merge([
            'title'     => 'Introduction to Eloquent',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'order'     => 1,
        ], $overrides);
    }

    // =========================================================================
    // 1. Unauthenticated requests return 401 JSON
    // =========================================================================

    public function test_unauthenticated_lesson_index_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->getJson("/api/courses/{$course->id}/lessons")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_lesson_show_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_lesson_store_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->postJson("/api/courses/{$course->id}/lessons", $this->validLessonPayload())
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_lesson_update_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", ['title' => 'New'])
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_lesson_destroy_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->deleteJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    // =========================================================================
    // 2. Students can view lessons of published courses
    // =========================================================================

    public function test_student_can_list_lessons_of_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $lesson  = $this->createLesson($course, ['title' => 'Visible Lesson']);

        $response = $this->actingAs($student)
                         ->getJson("/api/courses/{$course->id}/lessons");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.title', 'Visible Lesson');
    }

    public function test_student_can_view_single_lesson_of_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $lesson  = $this->createLesson($course);

        $response = $this->actingAs($student)
                         ->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $lesson->id);
    }

    // =========================================================================
    // 3. Students cannot view lessons of draft courses
    // =========================================================================

    public function test_student_cannot_list_lessons_of_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createLesson($course);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/lessons")
             ->assertStatus(403);
    }

    public function test_student_cannot_view_lesson_of_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(403);
    }

    // =========================================================================
    // 4. Teachers can view lessons in their own draft courses
    // =========================================================================

    public function test_teacher_can_list_lessons_in_own_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createLesson($course, ['title' => 'Draft Lesson']);

        $response = $this->actingAs($teacher)
                         ->getJson("/api/courses/{$course->id}/lessons");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.title', 'Draft Lesson');
    }

    public function test_teacher_can_view_lesson_in_own_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $lesson->id);
    }

    // =========================================================================
    // 5. Teachers cannot access lessons in another teacher's draft course
    // =========================================================================

    public function test_teacher_cannot_list_lessons_in_another_teachers_draft_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Draft->value]);
        $this->createLesson($course);

        $this->actingAs($other)
             ->getJson("/api/courses/{$course->id}/lessons")
             ->assertStatus(403);
    }

    public function test_teacher_cannot_view_lesson_in_another_teachers_draft_course(): void
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Draft->value]);
        $lesson = $this->createLesson($course);

        $this->actingAs($other)
             ->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(403);
    }

    // =========================================================================
    // 6. Students cannot create, update, or delete lessons
    // =========================================================================

    public function test_student_cannot_create_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($student)
             ->postJson("/api/courses/{$course->id}/lessons", $this->validLessonPayload())
             ->assertStatus(403);
    }

    public function test_student_cannot_update_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($student)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", ['title' => 'Hacked'])
             ->assertStatus(403);
    }

    public function test_student_cannot_delete_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($student)
             ->deleteJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(403);
    }

    // =========================================================================
    // 7. Teachers can create, update, and delete lessons in their own courses
    // =========================================================================

    public function test_teacher_can_create_lesson_in_own_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($teacher)
                         ->postJson("/api/courses/{$course->id}/lessons",
                             $this->validLessonPayload(['title' => 'My New Lesson']));

        $response->assertStatus(201)
                 ->assertJsonPath('data.title', 'My New Lesson');

        $this->assertDatabaseHas('lessons', [
            'course_id' => $course->id,
            'title'     => 'My New Lesson',
        ]);
    }

    public function test_teacher_can_update_own_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course, ['title' => 'Old Title']);

        $response = $this->actingAs($teacher)
                         ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}",
                             ['title' => 'New Title']);

        $response->assertStatus(200)
                 ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'title' => 'New Title']);
    }

    public function test_teacher_can_delete_own_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->deleteJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    // =========================================================================
    // 8. Teachers cannot modify lessons in another teacher's course
    // =========================================================================

    public function test_teacher_cannot_create_lesson_in_another_teachers_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);

        $this->actingAs($other)
             ->postJson("/api/courses/{$course->id}/lessons", $this->validLessonPayload())
             ->assertStatus(403);
    }

    public function test_teacher_cannot_update_lesson_in_another_teachers_course(): void
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);
        $lesson = $this->createLesson($course);

        $this->actingAs($other)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", ['title' => 'Stolen'])
             ->assertStatus(403);

        $this->assertDatabaseMissing('lessons', ['title' => 'Stolen']);
    }

    public function test_teacher_cannot_delete_lesson_in_another_teachers_course(): void
    {
        $owner  = User::factory()->teacher()->create();
        $other  = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);
        $lesson = $this->createLesson($course);

        $this->actingAs($other)
             ->deleteJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }

    // =========================================================================
    // 9. Mismatched course/lesson URL returns 404
    // =========================================================================

    public function test_lesson_from_different_course_returns_404_on_show(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $courseA  = $this->createCourse($teacher, ['title' => 'Course A']);
        $courseB  = $this->createCourse($teacher, ['title' => 'Course B']);
        $lessonB  = $this->createLesson($courseB);

        // Access lessonB through courseA's URL — must be 404
        $this->actingAs($teacher)
             ->getJson("/api/courses/{$courseA->id}/lessons/{$lessonB->id}")
             ->assertStatus(404);
    }

    public function test_lesson_from_different_course_returns_404_on_update(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->createCourse($teacher);
        $courseB = $this->createCourse($teacher);
        $lessonB = $this->createLesson($courseB);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$courseA->id}/lessons/{$lessonB->id}", ['title' => 'Wrong'])
             ->assertStatus(404);
    }

    public function test_lesson_from_different_course_returns_404_on_delete(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->createCourse($teacher);
        $courseB = $this->createCourse($teacher);
        $lessonB = $this->createLesson($courseB);

        $this->actingAs($teacher)
             ->deleteJson("/api/courses/{$courseA->id}/lessons/{$lessonB->id}")
             ->assertStatus(404);
    }

    // =========================================================================
    // 10. Invalid input returns 422 JSON validation errors
    // =========================================================================

    public function test_store_returns_422_when_title_missing(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons", [
                 'video_url' => 'https://example.com/video.mp4',
                 'order'     => 1,
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_video_url_missing(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons", [
                 'title' => 'A Lesson',
                 'order' => 1,
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['video_url']);
    }

    public function test_store_returns_422_when_order_missing(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons", [
                 'title'     => 'A Lesson',
                 'video_url' => 'https://example.com/video.mp4',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['order']);
    }

    public function test_store_returns_422_when_order_less_than_one(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons",
                 $this->validLessonPayload(['order' => 0]))
             ->assertStatus(422)
             ->assertJsonValidationErrors(['order']);
    }

    public function test_update_returns_422_when_title_is_empty_string(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", ['title' => ''])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    }

    // =========================================================================
    // 11. Client cannot change course_id through request input
    // =========================================================================

    public function test_store_ignores_client_supplied_course_id(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $courseA  = $this->createCourse($teacher);
        $courseB  = $this->createCourse($teacher);

        // Post to courseA but supply courseB's ID in the body
        $response = $this->actingAs($teacher)
                         ->postJson("/api/courses/{$courseA->id}/lessons",
                             $this->validLessonPayload(['course_id' => $courseB->id]));

        $response->assertStatus(201);

        $lessonId = $response->json('data.id');
        $this->assertDatabaseHas('lessons', [
            'id'        => $lessonId,
            'course_id' => $courseA->id,  // must be the route course, not the body value
        ]);
    }

    public function test_update_ignores_client_supplied_course_id(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $courseA  = $this->createCourse($teacher);
        $courseB  = $this->createCourse($teacher);
        $lesson   = $this->createLesson($courseA);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$courseA->id}/lessons/{$lesson->id}",
                 ['course_id' => $courseB->id, 'title' => 'Updated'])
             ->assertStatus(200);

        // course_id must not have changed
        $this->assertDatabaseHas('lessons', [
            'id'        => $lesson->id,
            'course_id' => $courseA->id,
        ]);
    }

    // =========================================================================
    // 12. Admin permissions
    // =========================================================================

    public function test_admin_can_list_lessons_of_any_course_including_draft(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createLesson($course, ['title' => 'Admin Sees This']);

        $response = $this->actingAs($admin)
                         ->getJson("/api/courses/{$course->id}/lessons");

        $response->assertStatus(200)
                 ->assertJsonPath('data.0.title', 'Admin Sees This');
    }

    public function test_admin_can_view_lesson_in_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $lesson  = $this->createLesson($course);

        $this->actingAs($admin)
             ->getJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $lesson->id);
    }

    public function test_admin_can_create_lesson_in_any_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($admin)
             ->postJson("/api/courses/{$course->id}/lessons",
                 $this->validLessonPayload(['title' => 'Admin Lesson']))
             ->assertStatus(201)
             ->assertJsonPath('data.title', 'Admin Lesson');
    }

    public function test_admin_can_update_any_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course, ['title' => 'Before']);

        $this->actingAs($admin)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}",
                 ['title' => 'Admin Updated'])
             ->assertStatus(200)
             ->assertJsonPath('data.title', 'Admin Updated');
    }

    public function test_admin_can_delete_any_lesson(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->actingAs($admin)
             ->deleteJson("/api/courses/{$course->id}/lessons/{$lesson->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    // =========================================================================
    // 13. Video URL validation — YouTube and Vimeo only (API-level)
    // =========================================================================

    /**
     * @dataProvider validVideoUrlProvider
     */
    public function test_store_accepts_valid_video_urls(string $url): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons", [
                 'title'     => 'Video Test Lesson',
                 'video_url' => $url,
                 'order'     => 1,
             ])
             ->assertStatus(201);
    }

    public static function validVideoUrlProvider(): array
    {
        return [
            'youtube.com watch'          => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtube.com no www'         => ['https://youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtu.be short link'        => ['https://youtu.be/dQw4w9WgXcQ'],
            'vimeo.com'                  => ['https://vimeo.com/123456789'],
            'vimeo.com with www'         => ['https://www.vimeo.com/123456789'],
            'youtube http scheme'        => ['http://www.youtube.com/watch?v=abc'],
            'youtube embed url'          => ['https://www.youtube.com/embed/dQw4w9WgXcQ'],
        ];
    }

    /**
     * @dataProvider invalidVideoUrlProvider
     */
    public function test_store_rejects_invalid_video_urls(string $url): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/lessons", [
                 'title'     => 'Video Test Lesson',
                 'video_url' => $url,
                 'order'     => 1,
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['video_url']);
    }

    public static function invalidVideoUrlProvider(): array
    {
        return [
            'unsupported domain'               => ['https://example.com/video.mp4'],
            'deceptive host youtube.com.evil'  => ['https://youtube.com.evil.com/watch?v=abc'],
            'deceptive host evil-youtube.com'  => ['https://evil-youtube.com/watch?v=abc'],
            'deceptive host sub.evil-vimeo'    => ['https://sub.vimeo.com.hacker.org/123'],
            'ftp scheme'                       => ['ftp://youtube.com/video'],
            'plain text non-url'               => ['not a url at all'],
            'empty-like string'                => ['   '],
            'missing scheme'                   => ['youtube.com/watch?v=abc'],
            'dailymotion'                      => ['https://www.dailymotion.com/video/x123'],
        ];
    }

    public function test_update_rejects_invalid_video_url(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", [
                 'video_url' => 'https://evil-youtube.com/watch?v=abc',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['video_url']);
    }

    public function test_update_accepts_valid_video_url(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", [
                 'video_url' => 'https://vimeo.com/987654321',
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.video_url', 'https://vimeo.com/987654321');
    }

    public function test_update_without_video_url_field_does_not_trigger_rule(): void
    {
        // 'sometimes' means the rule only fires when the field is present.
        // Omitting video_url entirely must not produce a validation error.
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);
        $lesson  = $this->createLesson($course);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/lessons/{$lesson->id}", [
                 'title' => 'Updated title only',
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.title', 'Updated title only');
    }
}
