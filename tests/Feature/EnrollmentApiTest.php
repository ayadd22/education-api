<?php

namespace Tests\Feature;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
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

    private function createEnrollment(User $student, Course $course, array $overrides = []): Enrollment
    {
        return Enrollment::create(array_merge([
            'student_id' => $student->id,
            'course_id'  => $course->id,
            'progress'   => 0,
        ], $overrides));
    }

    // =========================================================================
    // 1. Unauthenticated requests return 401 JSON
    // =========================================================================

    public function test_unauthenticated_index_returns_401(): void
    {
        $this->getJson('/api/enrollments')
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_store_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->postJson('/api/enrollments', ['course_id' => $course->id])
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_show_returns_401(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->getJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_destroy_returns_401(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->deleteJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    // =========================================================================
    // 2. A student can enroll in a published course
    // =========================================================================

    public function test_student_can_enroll_in_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $response = $this->actingAs($student)
                         ->postJson('/api/enrollments', ['course_id' => $course->id]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.student_id', $student->id)
                 ->assertJsonPath('data.course_id', $course->id)
                 ->assertJsonPath('data.progress', 0);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id'  => $course->id,
        ]);
    }

    // =========================================================================
    // 3. A student cannot enroll in a draft course
    // =========================================================================

    public function test_student_cannot_enroll_in_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $this->actingAs($student)
             ->postJson('/api/enrollments', ['course_id' => $course->id])
             ->assertStatus(403);

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'course_id'  => $course->id,
        ]);
    }

    // =========================================================================
    // 4. Teachers cannot enroll as students
    // =========================================================================

    public function test_teacher_cannot_enroll_in_a_course(): void
    {
        $teacher        = User::factory()->teacher()->create();
        $anotherTeacher = User::factory()->teacher()->create();
        $course         = $this->createCourse($anotherTeacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($teacher)
             ->postJson('/api/enrollments', ['course_id' => $course->id])
             ->assertStatus(403);
    }

    public function test_teacher_cannot_enroll_in_own_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($teacher)
             ->postJson('/api/enrollments', ['course_id' => $course->id])
             ->assertStatus(403);
    }

    // =========================================================================
    // 5. Duplicate enrollment is rejected
    // =========================================================================

    public function test_duplicate_enrollment_returns_422(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        // First enrollment
        $this->actingAs($student)
             ->postJson('/api/enrollments', ['course_id' => $course->id])
             ->assertStatus(201);

        // Second enrollment attempt — must be rejected
        $this->actingAs($student)
             ->postJson('/api/enrollments', ['course_id' => $course->id])
             ->assertStatus(422)
             ->assertJsonPath('errors.course_id.0', 'أنت مسجّل بالفعل في هذه الدورة.');

        // Only one record must exist
        $this->assertDatabaseCount('enrollments', 1);
    }

    // =========================================================================
    // 6. Client-supplied student_id or course_id cannot redirect enrollment
    // =========================================================================

    public function test_client_supplied_student_id_is_ignored(): void
    {
        $teacher      = User::factory()->teacher()->create();
        $student      = User::factory()->create();
        $victimStudent = User::factory()->create();
        $course       = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        // Attempt to enroll victimStudent by supplying their ID in the body
        $response = $this->actingAs($student)
                         ->postJson('/api/enrollments', [
                             'course_id'  => $course->id,
                             'student_id' => $victimStudent->id, // must be ignored
                         ]);

        $response->assertStatus(201);

        // Enrollment must belong to the authenticated student, not the victim
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'course_id'  => $course->id,
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $victimStudent->id,
            'course_id'  => $course->id,
        ]);
    }

    public function test_course_id_must_match_an_existing_course(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
             ->postJson('/api/enrollments', ['course_id' => 99999])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['course_id']);
    }

    // =========================================================================
    // 7. Index — students see only their own enrollments
    // =========================================================================

    public function test_student_sees_only_own_enrollments_in_index(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();

        $courseA = $this->createCourse($teacher, ['title' => 'Course A']);
        $courseB = $this->createCourse($teacher, ['title' => 'Course B']);

        $this->createEnrollment($studentA, $courseA);
        $this->createEnrollment($studentB, $courseB);

        $response = $this->actingAs($studentA)->getJson('/api/enrollments');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('course_id');
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($courseA->id));
        $this->assertFalse($ids->contains($courseB->id));
    }

    // =========================================================================
    // 8. Show — students cannot access another student's enrollment
    // =========================================================================

    public function test_student_can_view_own_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->actingAs($student)
             ->getJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $enrollment->id);
    }

    public function test_student_cannot_view_another_students_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $studentA   = User::factory()->create();
        $studentB   = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($studentA, $course);

        $this->actingAs($studentB)
             ->getJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(403);
    }

    // =========================================================================
    // 9. Admin permissions follow existing convention
    // =========================================================================

    public function test_admin_can_list_all_enrollments(): void
    {
        $teacher  = User::factory()->teacher()->create();
        $admin    = User::factory()->admin()->create();
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();

        $courseA = $this->createCourse($teacher);
        $courseB = $this->createCourse($teacher);

        $this->createEnrollment($studentA, $courseA);
        $this->createEnrollment($studentB, $courseB);

        $response = $this->actingAs($admin)->getJson('/api/enrollments');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_view_any_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $admin      = User::factory()->admin()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->actingAs($admin)
             ->getJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $enrollment->id);
    }

    public function test_admin_can_delete_any_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $admin      = User::factory()->admin()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->actingAs($admin)
             ->deleteJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    // =========================================================================
    // 10. Unenroll (destroy) — own record only
    // =========================================================================

    public function test_student_can_unenroll_from_own_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->actingAs($student)
             ->deleteJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(200)
             ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_student_cannot_delete_another_students_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $studentA   = User::factory()->create();
        $studentB   = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($studentA, $course);

        $this->actingAs($studentB)
             ->deleteJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
    }

    // =========================================================================
    // 12. Teacher accessing enrollments sees only their own (empty) list
    //     and cannot view another user's enrollment records
    // =========================================================================

    public function test_teacher_index_returns_empty_list_not_all_enrollments(): void
    {
        // A teacher has no enrollments. The filter must not leak other students' records.
        $teacher  = User::factory()->teacher()->create();
        $student  = User::factory()->create();
        $course   = $this->createCourse($teacher);
        $this->createEnrollment($student, $course);

        $response = $this->actingAs($teacher)->getJson('/api/enrollments');

        $response->assertStatus(200);
        // The teacher's filtered view must be empty — student's record must not appear
        $this->assertCount(0, $response->json('data'));
    }

    public function test_teacher_cannot_view_a_students_enrollment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher);
        $enrollment = $this->createEnrollment($student, $course);

        $this->actingAs($teacher)
             ->getJson("/api/enrollments/{$enrollment->id}")
             ->assertStatus(403);
    }

    // =========================================================================
    // 13. Admin enrollment (before() bypass allows admin to enroll)
    // =========================================================================

    public function test_admin_can_enroll_in_any_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $response = $this->actingAs($admin)
                         ->postJson('/api/enrollments', ['course_id' => $course->id]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.student_id', $admin->id)
                 ->assertJsonPath('data.course_id', $course->id);
    }

    public function test_store_returns_422_when_course_id_missing(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
             ->postJson('/api/enrollments', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['course_id']);
    }

    public function test_store_returns_422_when_course_id_is_not_integer(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
             ->postJson('/api/enrollments', ['course_id' => 'abc'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['course_id']);
    }
}
