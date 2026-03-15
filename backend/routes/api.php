<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Course routes
    Route::apiResource('courses', CourseController::class);

    // Instructor routes
    Route::apiResource('instructors', InstructorController::class);
    Route::get('instructors/all', [CourseController::class, 'getAllInstructors']);

    // Lesson routes
    Route::apiResource('lessons', LessonController::class);

    // Special endpoints
    Route::post('/courses/{course}/favorite', [CourseController::class, 'addFavorite']);
    Route::delete('/courses/{course}/favorite', [CourseController::class, 'removeFavorite']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
