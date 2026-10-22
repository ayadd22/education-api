<?php

/**
 * Standalone manual integration tests for the Education API.
 *
 * Run from the project root:
 *   php .\manual-tests\education.php
 *
 * Start the Laravel server in a separate terminal first:
 *   php artisan serve
 *
 * The script creates uniquely named test users/content and intentionally
 * leaves those records in the database for inspection. It never truncates
 * tables or deletes pre-existing data.
 */

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$baseUrl = rtrim(getenv('EDUCATION_API_URL') ?: 'http://127.0.0.1:8000/api', '/');
$runId = date('Ymd_His') . '_' . bin2hex(random_bytes(3));
$prefix = 'manual_' . $runId;

$results = [];
$created = [
    'users'       => [],
    'courses'     => [],
    'lessons'     => [],
    'assignments' => [],
    'enrollments' => [],
    'comments'    => [],
];

function printHeader(string $title): void
{
    echo PHP_EOL . str_repeat('=', 72) . PHP_EOL;
    echo '  ' . $title . PHP_EOL;
    echo str_repeat('=', 72) . PHP_EOL;
}

function check(bool $passed, string $message, array &$results): void
{
    $results[] = ['passed' => $passed, 'message' => $message];
    echo ($passed ? "PASS" : "FAIL") . " - {$message}" . PHP_EOL;
}

function requestApi(string $method, string $path, ?string $token = null, ?array $payload = null): array
{
    global $baseUrl;

    $url = $baseUrl . '/' . ltrim($path, '/');
    $headers = ['Accept: application/json'];

    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $body = null;
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException("Could not reach {$url}: {$curlError}");
    }

    $decoded = json_decode($raw, true);

    return [
        'status' => $status,
        'json' => is_array($decoded) ? $decoded : null,
        'raw' => $raw,
    ];
}

function makeTestUser(string $name, string $role, string $email): array
{
    $user = User::query()->where('email', $email)->first();

    if (!$user) {
        $user = User::unguarded(function () use ($name, $role, $email) {
            return User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('ManualTest-Only-123!'),
                'role' => $role,
            ]);
        });
    }

    $tokenName = 'manual-test-' . bin2hex(random_bytes(4));
    $token = $user->createToken($tokenName)->plainTextToken;

    return [$user, $token];
}

function dbHas(string $table, array $conditions): bool
{
    return \Illuminate\Support\Facades\DB::table($table)->where($conditions)->exists();
}

function responseDataId(array $response): ?int
{
    return isset($response['json']['data']['id']) ? (int) $response['json']['data']['id'] : null;
}

printHeader('EDUCATION API MANUAL INTEGRATION TEST');

echo "API URL: {$baseUrl}" . PHP_EOL;
echo "Run ID: {$runId}" . PHP_EOL;
echo "Database connection: " . config('database.default') . PHP_EOL;
echo "Database name: " . config('database.connections.' . config('database.default') . '.database') . PHP_EOL;
echo PHP_EOL;
echo "Safety: test records use unique names and are not automatically deleted." . PHP_EOL;
echo "Requirement: run `php artisan serve` in another terminal." . PHP_EOL;

try {
    // Confirm the API server is reachable before creating any test data.
    $probe = requestApi('GET', 'user');
    check(in_array($probe['status'], [401, 200], true), 'Laravel API server is reachable', $results);

    if (!in_array($probe['status'], [401, 200], true)) {
        throw new RuntimeException('API server did not return an expected response. Start `php artisan serve` and rerun.');
    }

    printHeader('1. PREPARE TEST USERS');

    [$student, $studentToken] = makeTestUser(
        'Manual Student ' . $runId,
        'student',
        $prefix . '_student@example.test'
    );
    [$teacher, $teacherToken] = makeTestUser(
        'Manual Teacher ' . $runId,
        'teacher',
        $prefix . '_teacher@example.test'
    );
    [$admin, $adminToken] = makeTestUser(
        'Manual Admin ' . $runId,
        'admin',
        $prefix . '_admin@example.test'
    );

    $created['users'] = [$student->id, $teacher->id, $admin->id];

    echo "Student ID: {$student->id}" . PHP_EOL;
    echo "Teacher ID: {$teacher->id}" . PHP_EOL;
    echo "Admin ID: {$admin->id}" . PHP_EOL;
    check(
        User::query()->whereIn('id', $created['users'])->count() === 3,
        'Student, teacher, and admin test users exist in the database',
        $results
    );

    printHeader('2. AUTHENTICATION CHECKS');

    $unauth = requestApi('GET', 'courses');
    echo "Unauthenticated GET /courses HTTP: {$unauth['status']}" . PHP_EOL;
    check($unauth['status'] === 401, 'Unauthenticated course listing is rejected with HTTP 401', $results);

    $studentCourses = requestApi('GET', 'courses', $studentToken);
    echo "Student GET /courses HTTP: {$studentCourses['status']}" . PHP_EOL;
    check($studentCourses['status'] === 200, 'Authenticated student can access course listing', $results);

    printHeader('3. CREATE COURSE THROUGH API');

    $coursePayload = [
        'title' => 'Manual Course ' . $runId,
        'description' => 'Course created by the standalone manual integration test.',
        'level' => 'beginner',
        'price' => 0,
        'status' => 'published',
    ];

    $courseResponse = requestApi('POST', 'courses', $teacherToken, $coursePayload);
    echo "HTTP Status: {$courseResponse['status']}" . PHP_EOL;

    $courseId = responseDataId($courseResponse);
    if ($courseId !== null) {
        $created['courses'][] = $courseId;
        echo "Course ID: {$courseId}" . PHP_EOL;
    } else {
        echo "Response: {$courseResponse['raw']}" . PHP_EOL;
    }

    check($courseResponse['status'] === 201 && $courseId !== null, 'Teacher can create a course through the API', $results);

    if ($courseId === null) {
        throw new RuntimeException('Course creation failed; dependent lesson, assignment, and enrollment tests cannot continue.');
    }

    check(
        dbHas('courses', ['id' => $courseId, 'teacher_id' => $teacher->id, 'title' => $coursePayload['title']]),
        'Course was persisted with the authenticated teacher as owner',
        $results
    );

    printHeader('4. COURSE OWNERSHIP AND VISIBILITY');

    $studentCourseResponse = requestApi('GET', 'courses/' . $courseId, $studentToken);
    echo "Student GET own published course HTTP: {$studentCourseResponse['status']}" . PHP_EOL;
    check($studentCourseResponse['status'] === 200, 'Student can view a published course', $results);

    $otherTeacherCourseResponse = requestApi('PUT', 'courses/' . $courseId, $adminToken, [
        'title' => 'Manual Updated Course ' . $runId,
    ]);
    echo "Admin update course HTTP: {$otherTeacherCourseResponse['status']}" . PHP_EOL;
    check(in_array($otherTeacherCourseResponse['status'], [200, 204], true), 'Admin can update course under the project admin-bypass policy', $results);

    printHeader('5. CREATE LESSON THROUGH API');

    $lessonPayload = [
        'title' => 'Manual Lesson ' . $runId,
        'description' => 'Lesson created by the manual integration test.',
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'order' => 1,
    ];

    $lessonResponse = requestApi('POST', "courses/{$courseId}/lessons", $teacherToken, $lessonPayload);
    echo "HTTP Status: {$lessonResponse['status']}" . PHP_EOL;
    $lessonId = responseDataId($lessonResponse);

    if ($lessonId !== null) {
        $created['lessons'][] = $lessonId;
        echo "Lesson ID: {$lessonId}" . PHP_EOL;
    } else {
        echo "Response: {$lessonResponse['raw']}" . PHP_EOL;
    }

    check($lessonResponse['status'] === 201 && $lessonId !== null, 'Owning teacher can create a lesson', $results);

    if ($lessonId !== null) {
        check(
            dbHas('lessons', ['id' => $lessonId, 'course_id' => $courseId, 'title' => $lessonPayload['title']]),
            'Lesson was persisted and linked to the correct course',
            $results
        );

        $lessonList = requestApi('GET', "courses/{$courseId}/lessons", $studentToken);
        check($lessonList['status'] === 200, 'Student can list lessons for a published course', $results);

        $invalidLesson = requestApi('POST', "courses/{$courseId}/lessons", $studentToken, $lessonPayload);
        echo "Student create lesson HTTP: {$invalidLesson['status']}" . PHP_EOL;
        check(in_array($invalidLesson['status'], [403, 401], true), 'Student cannot create a lesson', $results);
    } else {
        check(false, 'Lesson persistence and lesson authorization checks skipped because creation failed', $results);
    }

    printHeader('6. CREATE ASSIGNMENT THROUGH API');

    $assignmentPayload = [
        'title' => 'Manual Assignment ' . $runId,
        'description' => 'Assignment created by the manual integration test.',
    ];

    $assignmentResponse = requestApi('POST', "courses/{$courseId}/assignments", $teacherToken, $assignmentPayload);
    echo "HTTP Status: {$assignmentResponse['status']}" . PHP_EOL;
    $assignmentId = responseDataId($assignmentResponse);

    if ($assignmentId !== null) {
        $created['assignments'][] = $assignmentId;
        echo "Assignment ID: {$assignmentId}" . PHP_EOL;
    } else {
        echo "Response: {$assignmentResponse['raw']}" . PHP_EOL;
    }

    check($assignmentResponse['status'] === 201 && $assignmentId !== null, 'Owning teacher can create an assignment', $results);

    if ($assignmentId !== null) {
        check(
            dbHas('assignments', ['id' => $assignmentId, 'course_id' => $courseId, 'title' => $assignmentPayload['title']]),
            'Assignment was persisted and linked to the correct course',
            $results
        );

        $assignmentList = requestApi('GET', "courses/{$courseId}/assignments", $studentToken);
        check($assignmentList['status'] === 200, 'Student can list assignments for a published course', $results);

        $studentAssignment = requestApi('POST', "courses/{$courseId}/assignments", $studentToken, $assignmentPayload);
        echo "Student create assignment HTTP: {$studentAssignment['status']}" . PHP_EOL;
        check(in_array($studentAssignment['status'], [403, 401], true), 'Student cannot create an assignment', $results);
    } else {
        check(false, 'Assignment persistence and authorization checks skipped because creation failed', $results);
    }

    printHeader('7. ENROLLMENT DATABASE AND BUSINESS RULES');

    $enrollmentResponse = requestApi('POST', 'enrollments', $studentToken, ['course_id' => $courseId]);
    echo "Enroll HTTP Status: {$enrollmentResponse['status']}" . PHP_EOL;
    $enrollmentId = responseDataId($enrollmentResponse);

    if ($enrollmentId !== null) {
        $created['enrollments'][] = $enrollmentId;
        echo "Enrollment ID: {$enrollmentId}" . PHP_EOL;
    } else {
        echo "Response: {$enrollmentResponse['raw']}" . PHP_EOL;
    }

    check($enrollmentResponse['status'] === 201 && $enrollmentId !== null, 'Student can enroll in a published course', $results);

    if ($enrollmentId !== null) {
        check(
            dbHas('enrollments', [
                'id' => $enrollmentId,
                'student_id' => $student->id,
                'course_id' => $courseId,
                'progress' => 0,
            ]),
            'Enrollment row has correct student, course, and initial progress in the database',
            $results
        );
    } else {
        check(false, 'Enrollment database persistence check skipped because enrollment creation failed', $results);
    }

    $duplicateResponse = requestApi('POST', 'enrollments', $studentToken, ['course_id' => $courseId]);
    echo "Duplicate enrollment HTTP Status: {$duplicateResponse['status']}" . PHP_EOL;
    check($duplicateResponse['status'] === 422, 'Duplicate enrollment is rejected with HTTP 422', $results);

    $teacherEnrollment = requestApi('POST', 'enrollments', $teacherToken, ['course_id' => $courseId]);
    echo "Teacher enrollment HTTP Status: {$teacherEnrollment['status']}" . PHP_EOL;
    check(in_array($teacherEnrollment['status'], [403, 422], true), 'Teacher cannot enroll as a student', $results);

    $invalidEnrollment = requestApi('POST', 'enrollments', $studentToken, ['course_id' => 999999999]);
    echo "Invalid course enrollment HTTP Status: {$invalidEnrollment['status']}" . PHP_EOL;
    check($invalidEnrollment['status'] === 422, 'Enrollment with a non-existent course ID is rejected with HTTP 422', $results);

    $enrollmentList = requestApi('GET', 'enrollments', $studentToken);
    check($enrollmentList['status'] === 200, 'Student can list their enrollments', $results);

    if ($enrollmentId !== null) {
        $enrollmentShow = requestApi('GET', 'enrollments/' . $enrollmentId, $studentToken);
        check($enrollmentShow['status'] === 200, 'Student can view their own enrollment', $results);
    }

    printHeader('8. COMMENTS API');

    // --- Comments on a course ---
    $courseCommentResponse = requestApi('POST', "courses/{$courseId}/comments", $teacherToken, [
        'body' => 'Manual test comment on course ' . $runId,
    ]);
    echo "Teacher comment on course HTTP: {$courseCommentResponse['status']}" . PHP_EOL;
    $courseCommentId = responseDataId($courseCommentResponse);

    if ($courseCommentId !== null) {
        $created['comments'][] = $courseCommentId;
        echo "Course comment ID: {$courseCommentId}" . PHP_EOL;
    } else {
        echo "Response: {$courseCommentResponse['raw']}" . PHP_EOL;
    }

    check(
        $courseCommentResponse['status'] === 201 && $courseCommentId !== null,
        'Teacher can post a comment on their own course',
        $results
    );

    if ($courseCommentId !== null) {
        check(
            dbHas('comments', [
                'id'               => $courseCommentId,
                'user_id'          => $teacher->id,
                'commentable_type' => 'course',
                'commentable_id'   => $courseId,
                'body'             => 'Manual test comment on course ' . $runId,
            ]),
            'Course comment was persisted with correct polymorphic alias "course" in the database',
            $results
        );
    }

    // --- Enrolled student can comment on the course ---
    $studentCourseCommentResponse = requestApi('POST', "courses/{$courseId}/comments", $studentToken, [
        'body' => 'Student comment on course ' . $runId,
    ]);
    echo "Student comment on enrolled course HTTP: {$studentCourseCommentResponse['status']}" . PHP_EOL;

    // Student is enrolled (section 7 enrolled them). This should succeed.
    check(
        $studentCourseCommentResponse['status'] === 201,
        'Enrolled student can post a comment on a published course',
        $results
    );

    if (isset($studentCourseCommentResponse['json']['data']['id'])) {
        $created['comments'][] = (int) $studentCourseCommentResponse['json']['data']['id'];
    }

    // --- Listing course comments ---
    $courseCommentList = requestApi('GET', "courses/{$courseId}/comments", $studentToken);
    echo "List course comments HTTP: {$courseCommentList['status']}" . PHP_EOL;
    check(
        $courseCommentList['status'] === 200,
        'Authenticated user can list comments for a published course',
        $results
    );

    if ($courseCommentList['status'] === 200) {
        $commentCount = count($courseCommentList['json']['data'] ?? []);
        echo "Comment count in listing: {$commentCount}" . PHP_EOL;
        check(
            $commentCount >= 2,
            'Course comment listing returns the expected comments (at least the two just created)',
            $results
        );

        // Verify eager-loaded author is present
        $firstComment = $courseCommentList['json']['data'][0] ?? null;
        check(
            $firstComment !== null && isset($firstComment['user']['id'], $firstComment['user']['name']),
            'Comment listing includes eager-loaded author data (user.id and user.name)',
            $results
        );
    }

    // --- Comments on a lesson ---
    if ($lessonId !== null) {
        $lessonCommentResponse = requestApi(
            'POST',
            "courses/{$courseId}/lessons/{$lessonId}/comments",
            $teacherToken,
            ['body' => 'Manual lesson comment ' . $runId]
        );
        echo "Teacher comment on lesson HTTP: {$lessonCommentResponse['status']}" . PHP_EOL;
        $lessonCommentId = responseDataId($lessonCommentResponse);

        check(
            $lessonCommentResponse['status'] === 201 && $lessonCommentId !== null,
            'Teacher can post a comment on their own lesson',
            $results
        );

        if ($lessonCommentId !== null) {
            $created['comments'][] = $lessonCommentId;
            check(
                dbHas('comments', [
                    'id'               => $lessonCommentId,
                    'commentable_type' => 'lesson',
                    'commentable_id'   => $lessonId,
                ]),
                'Lesson comment was persisted with correct polymorphic alias "lesson"',
                $results
            );
        }
    }

    // --- Comments on an assignment ---
    if ($assignmentId !== null) {
        $assignmentCommentResponse = requestApi(
            'POST',
            "courses/{$courseId}/assignments/{$assignmentId}/comments",
            $studentToken,
            ['body' => 'Manual assignment comment ' . $runId]
        );
        echo "Student comment on assignment HTTP: {$assignmentCommentResponse['status']}" . PHP_EOL;
        $assignmentCommentId = responseDataId($assignmentCommentResponse);

        check(
            $assignmentCommentResponse['status'] === 201 && $assignmentCommentId !== null,
            'Enrolled student can post a comment on an assignment in an enrolled published course',
            $results
        );

        if ($assignmentCommentId !== null) {
            $created['comments'][] = $assignmentCommentId;
            check(
                dbHas('comments', [
                    'id'               => $assignmentCommentId,
                    'commentable_type' => 'assignment',
                    'commentable_id'   => $assignmentId,
                ]),
                'Assignment comment was persisted with correct polymorphic alias "assignment"',
                $results
            );
        }
    }

    // --- Unauthenticated access is rejected ---
    $unauthComment = requestApi('GET', "courses/{$courseId}/comments");
    echo "Unauthenticated list comments HTTP: {$unauthComment['status']}" . PHP_EOL;
    check(
        $unauthComment['status'] === 401,
        'Unauthenticated request to list comments is rejected with HTTP 401',
        $results
    );

    // --- Validation: empty body is rejected ---
    $invalidComment = requestApi('POST', "courses/{$courseId}/comments", $teacherToken, ['body' => '']);
    echo "Empty body comment HTTP: {$invalidComment['status']}" . PHP_EOL;
    check(
        $invalidComment['status'] === 422,
        'Comment with empty body is rejected with HTTP 422',
        $results
    );

    // --- Author can delete their own comment ---
    if ($courseCommentId !== null) {
        $deleteResponse = requestApi('DELETE', "comments/{$courseCommentId}", $teacherToken);
        echo "Teacher delete own comment HTTP: {$deleteResponse['status']}" . PHP_EOL;
        check(
            $deleteResponse['status'] === 200,
            'Comment author can delete their own comment',
            $results
        );
        check(
            ! dbHas('comments', ['id' => $courseCommentId]),
            'Deleted comment no longer exists in the database',
            $results
        );
    }

    // --- Other user cannot delete someone else's comment ---
    if (isset($studentCourseCommentResponse['json']['data']['id'])) {
        $studentCommentId = (int) $studentCourseCommentResponse['json']['data']['id'];
        $forbiddenDelete  = requestApi('DELETE', "comments/{$studentCommentId}", $teacherToken);
        echo "Teacher delete student comment HTTP: {$forbiddenDelete['status']}" . PHP_EOL;
        check(
            $forbiddenDelete['status'] === 403,
            'A user who did not author a comment cannot delete it (HTTP 403)',
            $results
        );
    }

    // --- Admin can delete any comment ---
    if (isset($studentCourseCommentResponse['json']['data']['id'])) {
        $studentCommentId  = (int) $studentCourseCommentResponse['json']['data']['id'];
        $adminDeleteResponse = requestApi('DELETE', "comments/{$studentCommentId}", $adminToken);
        echo "Admin delete any comment HTTP: {$adminDeleteResponse['status']}" . PHP_EOL;
        check(
            $adminDeleteResponse['status'] === 200,
            'Admin can delete any comment regardless of authorship',
            $results
        );
        check(
            ! dbHas('comments', ['id' => $studentCommentId]),
            'Admin-deleted comment no longer exists in the database',
            $results
        );
    }

    printHeader('9. VALIDATION CHECKS');

    $invalidCourse = requestApi('POST', 'courses', $teacherToken, [
        'title' => '',
        'level' => 'not-a-level',
        'price' => -5,
    ]);
    echo "Invalid course HTTP Status: {$invalidCourse['status']}" . PHP_EOL;
    check($invalidCourse['status'] === 422, 'Invalid course payload is rejected with HTTP 422', $results);

    $invalidLesson = requestApi('POST', "courses/{$courseId}/lessons", $teacherToken, [
        'title' => '',
        'video_url' => '',
        'order' => 0,
    ]);
    echo "Invalid lesson HTTP Status: {$invalidLesson['status']}" . PHP_EOL;
    check($invalidLesson['status'] === 422, 'Invalid lesson payload is rejected with HTTP 422', $results);

    $invalidAssignment = requestApi('POST', "courses/{$courseId}/assignments", $teacherToken, [
        'title' => '',
    ]);
    echo "Invalid assignment HTTP Status: {$invalidAssignment['status']}" . PHP_EOL;
    check($invalidAssignment['status'] === 422, 'Invalid assignment payload is rejected with HTTP 422', $results);

    printHeader('10. FINAL DATABASE CHECKS');

    echo "Users created/reused: " . count($created['users']) . PHP_EOL;
    echo "Courses created: " . count($created['courses']) . PHP_EOL;
    echo "Lessons created: " . count($created['lessons']) . PHP_EOL;
    echo "Assignments created: " . count($created['assignments']) . PHP_EOL;
    echo "Enrollments created: " . count($created['enrollments']) . PHP_EOL;
    echo "Comments created: " . count($created['comments']) . PHP_EOL;

    check(
        User::query()->whereIn('id', $created['users'])->count() === 3,
        'All three test users remain in the database',
        $results
    );

} catch (Throwable $e) {
    echo PHP_EOL . 'FATAL TEST RUNNER ERROR: ' . $e->getMessage() . PHP_EOL;
    $results[] = ['passed' => false, 'message' => 'Test runner stopped: ' . $e->getMessage()];
}

printHeader('FINAL SUMMARY');

$total = count($results);
$passed = count(array_filter($results, fn ($result) => $result['passed']));
$failed = $total - $passed;

echo "Total tests: {$total}" . PHP_EOL;
echo "Passed:      {$passed}" . PHP_EOL;
echo "Failed:      {$failed}" . PHP_EOL;

if ($failed > 0) {
    echo PHP_EOL . "Some checks failed. Review the HTTP responses and Laravel logs before changing application code." . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "All checks passed." . PHP_EOL;
exit(0);
