<?php

use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LessonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('courses', CourseController::class);

    Route::apiResource('courses.lessons', LessonController::class)
         ->shallow(false);

    Route::apiResource('courses.assignments', AssignmentController::class)
         ->shallow(false);

    Route::apiResource('enrollments', EnrollmentController::class)
         ->only(['index', 'store', 'show', 'destroy']);

    

    // Comments on courses
    Route::get('courses/{course}/comments',  [CommentController::class, 'indexForCourse']);
    Route::post('courses/{course}/comments', [CommentController::class, 'storeForCourse']);

    // Comments on lessons
    Route::get('courses/{course}/lessons/{lesson}/comments',  [CommentController::class, 'indexForLesson']);
    Route::post('courses/{course}/lessons/{lesson}/comments', [CommentController::class, 'storeForLesson']);

    // Comments on assignments
    Route::get('courses/{course}/assignments/{assignment}/comments',  [CommentController::class, 'indexForAssignment']);
    Route::post('courses/{course}/assignments/{assignment}/comments', [CommentController::class, 'storeForAssignment']);

    // Delete any comment by its own ID (author or admin)
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
});
