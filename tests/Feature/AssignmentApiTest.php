<?php

namespace Tests\Feature;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
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
            'description' => 'A test description.',
            'level'       => CourseLevel::Beginner->value,
            'status'      => CourseStatus::Published->value,
            'price'       => 0,
        ], $overrides));
    }

    private function createAssignment(Course $course, array $overrides = []): Assignment
    {
        return Assignment::create(array_merge([
            'course_id'   => $course->id,
            'title'       => 'Test Assignment',
            'description' => 'A test assignment description.',
        ], $overrides));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'       => 'Build a REST API',
            'description' => 'Implement all endpoints with tests.',
        ], $overrides);
    }

    // =========================================================================
    // 1. Unauthenticated requests return 401 JSON
    // =========================================================================

    public function test_unauthenticated_index_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_show_returns_401(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_store_returns_401(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->postJson("/api/courses/{$course->id}/assignments", $this->validPayload())
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_update_returns_401(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}", ['title' => 'X'])
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_unauthenticated_destroy_returns_401(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->deleteJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
    }

    // =========================================================================
    // 2. Listing and visibility
    // =========================================================================

    public function test_teacher_can_list_assignments_in_own_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->createAssignment($course, ['title' => 'My Assignment']);

        $this->actingAs($teacher)
             ->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(200)
             ->assertJsonPath('data.0.title', 'My Assignment');
    }

    public function test_teacher_can_list_assignments_in_own_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createAssignment($course, ['title' => 'Draft Assignment']);

        $this->actingAs($teacher)
             ->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(200)
             ->assertJsonPath('data.0.title', 'Draft Assignment');
    }

    public function test_teacher_cannot_list_assignments_in_another_teachers_draft_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Draft->value]);
        $this->createAssignment($course);

        $this->actingAs($other)
             ->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(403);
    }

    public function test_student_can_list_assignments_in_published_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $this->createAssignment($course, ['title' => 'Visible']);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(200)
             ->assertJsonPath('data.0.title', 'Visible');
    }

    public function test_student_cannot_list_assignments_in_draft_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createAssignment($course);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(403);
    }

    public function test_admin_can_list_assignments_in_any_course_including_draft(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $this->createAssignment($course, ['title' => 'Admin Sees This']);

        $this->actingAs($admin)
             ->getJson("/api/courses/{$course->id}/assignments")
             ->assertStatus(200)
             ->assertJsonPath('data.0.title', 'Admin Sees This');
    }

    // =========================================================================
    // 3. Show and parent-child integrity
    // =========================================================================

    public function test_authorized_user_can_show_assignment_in_correct_course(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course, ['title' => 'Correct']);

        $this->actingAs($teacher)
             ->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $assignment->id)
             ->assertJsonPath('data.title', 'Correct');
    }

    public function test_assignment_from_different_course_returns_404_on_show(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $courseA    = $this->createCourse($teacher);
        $courseB    = $this->createCourse($teacher);
        $assignmentB = $this->createAssignment($courseB);

        $this->actingAs($teacher)
             ->getJson("/api/courses/{$courseA->id}/assignments/{$assignmentB->id}")
             ->assertStatus(404);
    }

    public function test_student_cannot_show_assignment_in_draft_course(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(403);
    }

    public function test_student_can_show_assignment_in_published_course(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($student)
             ->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $assignment->id);
    }

    public function test_admin_can_show_assignment_in_draft_course(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $admin      = User::factory()->admin()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Draft->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($admin)
             ->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $assignment->id);
    }

    // =========================================================================
    // 4. Create
    // =========================================================================

    public function test_teacher_can_create_assignment_in_own_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $response = $this->actingAs($teacher)
                         ->postJson("/api/courses/{$course->id}/assignments",
                             $this->validPayload(['title' => 'New Assignment']));

        $response->assertStatus(201)
                 ->assertJsonPath('data.title', 'New Assignment');

        $this->assertDatabaseHas('assignments', [
            'course_id' => $course->id,
            'title'     => 'New Assignment',
        ]);
    }

    public function test_teacher_cannot_create_assignment_in_another_teachers_course(): void
    {
        $owner = User::factory()->teacher()->create();
        $other = User::factory()->teacher()->create();
        $course = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);

        $this->actingAs($other)
             ->postJson("/api/courses/{$course->id}/assignments", $this->validPayload())
             ->assertStatus(403);
    }

    public function test_student_cannot_create_assignment(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();
        $course  = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);

        $this->actingAs($student)
             ->postJson("/api/courses/{$course->id}/assignments", $this->validPayload())
             ->assertStatus(403);
    }

    public function test_admin_can_create_assignment_in_any_course(): void
    {
        $teacher = User::factory()->teacher()->create();
        $admin   = User::factory()->admin()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($admin)
             ->postJson("/api/courses/{$course->id}/assignments",
                 $this->validPayload(['title' => 'Admin Assignment']))
             ->assertStatus(201)
             ->assertJsonPath('data.title', 'Admin Assignment');
    }

    public function test_store_returns_422_when_title_missing(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/assignments", [
                 'description' => 'Missing title.',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    }

    public function test_store_returns_422_when_description_missing(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/assignments", [
                 'title' => 'No description',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['description']);
    }

    public function test_store_ignores_client_supplied_course_id(): void
    {
        $teacher = User::factory()->teacher()->create();
        $courseA = $this->createCourse($teacher);
        $courseB = $this->createCourse($teacher);

        $response = $this->actingAs($teacher)
                         ->postJson("/api/courses/{$courseA->id}/assignments",
                             $this->validPayload(['course_id' => $courseB->id]));

        $response->assertStatus(201);

        $this->assertDatabaseHas('assignments', [
            'id'        => $response->json('data.id'),
            'course_id' => $courseA->id,
        ]);
    }

    // =========================================================================
    // 5. Update
    // =========================================================================

    public function test_teacher_can_update_own_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course, ['title' => 'Old Title']);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}",
                 ['title' => 'New Title'])
             ->assertStatus(200)
             ->assertJsonPath('data.title', 'New Title');

        $this->assertDatabaseHas('assignments', [
            'id'    => $assignment->id,
            'title' => 'New Title',
        ]);
    }

    public function test_partial_update_works_without_resubmitting_all_fields(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course, [
            'title'       => 'Original',
            'description' => 'Original description.',
        ]);

        // Send only title — description must remain unchanged
        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}",
                 ['title' => 'Updated Title'])
             ->assertStatus(200)
             ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('assignments', [
            'id'          => $assignment->id,
            'title'       => 'Updated Title',
            'description' => 'Original description.',
        ]);
    }

    public function test_teacher_cannot_update_another_teachers_assignment(): void
    {
        $owner      = User::factory()->teacher()->create();
        $other      = User::factory()->teacher()->create();
        $course     = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($other)
             ->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}",
                 ['title' => 'Stolen'])
             ->assertStatus(403);

        $this->assertDatabaseMissing('assignments', ['title' => 'Stolen']);
    }

    public function test_student_cannot_update_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($student)
             ->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}",
                 ['title' => 'Hacked'])
             ->assertStatus(403);
    }

    public function test_assignment_from_different_course_returns_404_on_update(): void
    {
        $teacher     = User::factory()->teacher()->create();
        $courseA     = $this->createCourse($teacher);
        $courseB     = $this->createCourse($teacher);
        $assignmentB = $this->createAssignment($courseB);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$courseA->id}/assignments/{$assignmentB->id}",
                 ['title' => 'Wrong'])
             ->assertStatus(404);
    }

    public function test_update_returns_422_when_title_is_empty_string(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}",
                 ['title' => ''])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['title']);
    }

    public function test_update_ignores_client_supplied_course_id(): void
    {
        $teacher     = User::factory()->teacher()->create();
        $courseA     = $this->createCourse($teacher);
        $courseB     = $this->createCourse($teacher);
        $assignment  = $this->createAssignment($courseA);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$courseA->id}/assignments/{$assignment->id}",
                 ['course_id' => $courseB->id, 'title' => 'Updated'])
             ->assertStatus(200);

        $this->assertDatabaseHas('assignments', [
            'id'        => $assignment->id,
            'course_id' => $courseA->id,
        ]);
    }

    // =========================================================================
    // 6. Delete
    // =========================================================================

    public function test_teacher_can_delete_own_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->actingAs($teacher)
             ->deleteJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_teacher_cannot_delete_another_teachers_assignment(): void
    {
        $owner      = User::factory()->teacher()->create();
        $other      = User::factory()->teacher()->create();
        $course     = $this->createCourse($owner, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($other)
             ->deleteJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
    }

    public function test_student_cannot_delete_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $student    = User::factory()->create();
        $course     = $this->createCourse($teacher, ['status' => CourseStatus::Published->value]);
        $assignment = $this->createAssignment($course);

        $this->actingAs($student)
             ->deleteJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(403);

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
    }

    public function test_assignment_from_different_course_returns_404_on_delete(): void
    {
        $teacher     = User::factory()->teacher()->create();
        $courseA     = $this->createCourse($teacher);
        $courseB     = $this->createCourse($teacher);
        $assignmentB = $this->createAssignment($courseB);

        $this->actingAs($teacher)
             ->deleteJson("/api/courses/{$courseA->id}/assignments/{$assignmentB->id}")
             ->assertStatus(404);
    }

    public function test_admin_can_delete_any_assignment(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $admin      = User::factory()->admin()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->actingAs($admin)
             ->deleteJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    // =========================================================================
    // 7. Response format and status codes
    // =========================================================================

    public function test_store_returns_201_with_data_key(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course  = $this->createCourse($teacher);

        $this->actingAs($teacher)
             ->postJson("/api/courses/{$course->id}/assignments", $this->validPayload())
             ->assertStatus(201)
             ->assertJsonStructure(['data' => ['id', 'course_id', 'title', 'description']]);
    }

    public function test_show_returns_200_with_data_key(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->actingAs($teacher)
             ->getJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200)
             ->assertJsonStructure(['data' => ['id', 'course_id', 'title', 'description']]);
    }

    public function test_update_returns_200_with_data_key(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->actingAs($teacher)
             ->patchJson("/api/courses/{$course->id}/assignments/{$assignment->id}",
                 ['title' => 'Patched'])
             ->assertStatus(200)
             ->assertJsonStructure(['data' => ['id', 'title']]);
    }

    public function test_destroy_returns_200_with_message_key(): void
    {
        $teacher    = User::factory()->teacher()->create();
        $course     = $this->createCourse($teacher);
        $assignment = $this->createAssignment($course);

        $this->actingAs($teacher)
             ->deleteJson("/api/courses/{$course->id}/assignments/{$assignment->id}")
             ->assertStatus(200)
             ->assertJsonStructure(['message']);
    }
}
