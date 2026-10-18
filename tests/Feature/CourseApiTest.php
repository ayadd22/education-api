<?php

namespace Tests\Feature;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Create a Course row directly without a factory (Course has no HasFactory). */
    private function createCourse(User $teacher, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'teacher_id'  => $teacher->id,
            'title'       => 'Test Course',
            'description' => 'A test course description.',
            'level'       => CourseLevel::Beginner->value,
            'status'      => CourseStatus::Published->value,
            'price'       => 0,
        ], $overrides));
    }

    private function validCoursePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Introduction to Laravel',
            'level' => CourseLevel::Beginner->value,
            'price' => 99.99,
        ], $overrides);
    }

    // =========================================================================
    // 1. Unauthenticated requests return 401 JSON
    // =========================================================================

    public function test_unauthenticated_index_returns_401(): void
    {
        $response = $this->getJson('/api/courses');

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_show_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->getJson("/api/courses/{$course->id}");

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_store_returns_401(): void
    {
        $response = $this->postJson('/api/courses', $this->validCoursePayload());

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_update_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->patchJson("/api/courses/{$course->id}", ['title' => 'New Title']);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_destroy_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    // =========================================================================
    // 2. Students can list and view published courses but cannot view drafts
    // =========================================================================

    public function test_student_can_list_published_courses(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(); // default role = student

        $this->createCourse($teacher, ['status' => CourseStatus::Published->value, 'title' => 'Published']);
        $this->createCourse($teacher, ['status' => CourseStatus::Draft->value,     'title' => 'Draft']);

        $response = $this->actingAs($student)->getJson('/api/courses');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Published'));
        $this->assertFalse($titles->contains('Draft'));
    }

    public function test_student_can_view_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $response = $this->actingAs($student)->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $course->id);
    }

    public function test_student_cannot_view_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $response = $this->actingAs($student)->getJson("/api/courses/{$course->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // 3. Teachers can view their own drafts but not another teacher's drafts
    // =========================================================================

    public function test_teacher_can_view_own_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $response = $this->actingAs($teacher)->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $course->id);
    }

    public function test_teacher_cannot_view_another_teachers_draft(): void
    {
        $ownerTeacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $course       = $this->createCourse($ownerTeacher, ['status' => CourseStatus::Draft->value]);

        $response = $this->actingAs($otherTeacher)->getJson("/api/courses/{$course->id}");

        $response->assertStatus(403);
    }

    public function test_teacher_sees_own_drafts_in_index_but_not_other_teachers_drafts(): void
    {
        $ownerTeacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $this->createCourse($ownerTeacher, ['status' => CourseStatus::Draft->value,     'title' => 'Own Draft']);
        $this->createCourse($otherTeacher, ['status' => CourseStatus::Draft->value,     'title' => 'Other Draft']);
        $this->createCourse($otherTeacher, ['status' => CourseStatus::Published->value, 'title' => 'Other Published']);

        $response = $this->actingAs($ownerTeacher)->getJson('/api/courses');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Own Draft'));
        $this->assertFalse($titles->contains('Other Draft'));
        $this->assertTrue($titles->contains('Other Published'));
    }

    // =========================================================================
    // 4. Teachers cannot update or delete another teacher's courses
    // =========================================================================

    public function test_teacher_cannot_update_another_teachers_course(): void
    {
        $ownerTeacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $course       = $this->createCourse($ownerTeacher);

        $response = $this->actingAs($otherTeacher)
                         ->patchJson("/api/courses/{$course->id}", ['title' => 'Hijacked']);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('courses', ['title' => 'Hijacked']);
    }

    public function test_teacher_cannot_delete_another_teachers_course(): void
    {
        $ownerTeacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $course       = $this->createCourse($ownerTeacher);

        $response = $this->actingAs($otherTeacher)
                         ->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    // =========================================================================
    // 5. Students cannot create, update, or delete courses
    // =========================================================================

    public function test_student_cannot_create_course(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)
                         ->postJson('/api/courses', $this->validCoursePayload());

        $response->assertStatus(403);
    }

    public function test_student_cannot_update_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($student)
                         ->patchJson("/api/courses/{$course->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    }

    public function test_student_cannot_delete_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($student)
                         ->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // 6. Course creation always assigns teacher_id from the authenticated user
    // =========================================================================

    public function test_store_assigns_teacher_id_from_authenticated_user(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', $this->validCoursePayload());

        $response->assertStatus(201);

        $courseId = $response->json('data.id');
        $this->assertDatabaseHas('courses', [
            'id'         => $courseId,
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_store_ignores_client_supplied_teacher_id(): void
    {
        $teacher      = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        $payload = $this->validCoursePayload(['teacher_id' => $otherTeacher->id]);

        $response = $this->actingAs($teacher)->postJson('/api/courses', $payload);

        $response->assertStatus(201);

        $courseId = $response->json('data.id');
        $this->assertDatabaseHas('courses', [
            'id'         => $courseId,
            'teacher_id' => $teacher->id, // must be the authenticated teacher
        ]);
    }

    // =========================================================================
    // 7. Admins can access all courses including drafts, and manage any course
    // =========================================================================

    public function test_admin_can_list_all_courses_including_drafts(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();

        $this->createCourse($teacher, ['status' => CourseStatus::Published->value, 'title' => 'Published']);
        $this->createCourse($teacher, ['status' => CourseStatus::Draft->value,     'title' => 'Draft']);

        $response = $this->actingAs($admin)->getJson('/api/courses');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Published'));
        $this->assertTrue($titles->contains('Draft'));
    }

    public function test_admin_can_view_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $response = $this->actingAs($admin)->getJson("/api/courses/{$course->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.id', $course->id);
    }

    public function test_admin_can_update_any_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($admin)
                         ->patchJson("/api/courses/{$course->id}", ['title' => 'Admin Updated']);

        $response->assertStatus(200)
                 ->assertJsonPath('data.title', 'Admin Updated');
    }

    public function test_admin_can_delete_any_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($admin)->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    // =========================================================================
    // 8. Invalid input returns validation errors as JSON
    // =========================================================================

    public function test_store_returns_422_when_title_missing(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', [
                             'level' => CourseLevel::Beginner->value,
                             'price' => 0,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_level_invalid(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', $this->validCoursePayload(['level' => 'expert']));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['level']);
    }

    public function test_store_returns_422_when_price_negative(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', $this->validCoursePayload(['price' => -10]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    public function test_store_returns_422_when_status_invalid(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', $this->validCoursePayload(['status' => 'archived']));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    public function test_update_returns_422_when_title_empty_string(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($teacher)
                         ->patchJson("/api/courses/{$course->id}", ['title' => '']);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    public function test_store_omitting_status_creates_draft_by_default(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', $this->validCoursePayload());
        // no 'status' key in payload

        $response->assertStatus(201);

        $courseId = $response->json('data.id');
        $this->assertDatabaseHas('courses', [
            'id'     => $courseId,
            'status' => CourseStatus::Draft->value,
        ]);
    }

    // =========================================================================
    // 9. Listing is paginated and follows visibility rules
    // =========================================================================

    public function test_index_returns_paginated_structure(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->createCourse($teacher, [
                'title'  => "Course {$i}",
                'status' => CourseStatus::Published->value,
            ]);
        }

        $response = $this->actingAs($student)->getJson('/api/courses');

        // response()->json($paginator) serialises the paginator with all pagination
        // keys at the root level (current_page, data, last_page, per_page, total,
        // next_page_url, prev_page_url, etc.) — not wrapped in a 'meta' key.
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'current_page',
                     'last_page',
                     'per_page',
                     'total',
                     'next_page_url',
                     'prev_page_url',
                 ]);
    }

    public function test_index_draft_courses_excluded_from_student_pagination(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);

        $response = $this->actingAs($student)->getJson('/api/courses');

        $response->assertStatus(200);

        // The paginator's 'total' key is at the root when using response()->json().
        $this->assertEquals(1, $response->json('total'));
    }

    // =========================================================================
    // Teacher can manage their own courses (positive ownership paths)
    // =========================================================================

    public function test_teacher_can_create_course(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)
                         ->postJson('/api/courses', $this->validCoursePayload(['title' => 'My New Course']));

        $response->assertStatus(201)
                 ->assertJsonPath('data.title', 'My New Course');
    }

    public function test_teacher_can_update_own_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['title' => 'Original Title']);

        $response = $this->actingAs($teacher)
                         ->patchJson("/api/courses/{$course->id}", ['title' => 'Updated Title']);

        $response->assertStatus(200)
                 ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_teacher_can_delete_own_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($teacher)->deleteJson("/api/courses/{$course->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }
}
