<?php

use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseReportController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Endpoints under test. The controllers belong to each implementation branch.
Route::middleware(['peak_memory', 'auth:api'])->group(function () {
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/teachers/{id}', [TeacherController::class, 'show'])->whereNumber('id');

    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show'])->whereNumber('id');

    Route::get('/students/{id}/enrollments', [EnrollmentController::class, 'index'])->whereNumber('id');

    Route::get('/reports/courses', [CourseReportController::class, 'index']);
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
