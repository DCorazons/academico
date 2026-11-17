<?php

use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseEnrollmentController;
use App\Http\Controllers\Api\CourseMaterialController;
use App\Http\Controllers\Api\CourseReportController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExamSubmissionController;
use App\Http\Controllers\Api\StudentDashboardController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Endpoints under test. The controllers belong to each implementation branch.
Route::middleware(['peak_memory', 'auth:api', 'api_role:admin'])->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}/enrollments', [CourseEnrollmentController::class, 'index'])->whereNumber('id');
    Route::get('/reports/courses', [CourseReportController::class, 'index']);
});

Route::middleware(['peak_memory', 'auth:api', 'api_role:student'])->group(function () {
    Route::get('/me/dashboard', [StudentDashboardController::class, 'show']);
    Route::get('/courses/{id}/materials', [CourseMaterialController::class, 'index'])->whereNumber('id');
    Route::get('/exams/{id}', [ExamController::class, 'show'])->whereNumber('id');
    Route::post('/exams/{id}/submissions', [ExamSubmissionController::class, 'store'])->whereNumber('id');
});

// Mock Ecuasolutions endpoints for local testing only
if (app()->environment('local', 'testing')) {
    Route::get('/mock/ecuasolutions/ping', function () {
        return response()->json(['status' => 'online']);
    })->name('mock.ecuasolutions.ping');

    Route::post('/mock/ecuasolutions', function () {
        $data = request()->all();
        Log::info('[MockEcuasolutions] Received invoice', ['numtrans' => $data['numtrans'] ?? null]);

        return response()->json([
            'mensaje' => 'MOCK-'.str_pad($data['numtrans'] ?? rand(1000, 9999), 6, '0', STR_PAD_LEFT),
        ]);
    })->name('mock.ecuasolutions');
}
