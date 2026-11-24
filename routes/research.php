<?php

use App\Http\Controllers\Research\LoginController;
use App\Http\Controllers\Web\CourseController;
use App\Http\Controllers\Web\CourseEnrollmentController;
use App\Http\Controllers\Web\CourseMaterialController;
use App\Http\Controllers\Web\CourseReportController;
use App\Http\Controllers\Web\ExamController;
use App\Http\Controllers\Web\ExamSubmissionController;
use App\Http\Controllers\Web\StudentDashboardController;
use Illuminate\Support\Facades\Route;

// Server-rendered pages under test, loaded with the web middleware group (session,
// CSRF, SetLocale) in bootstrap/app.php. Users log in with a session like on the real
// student pages. The Web controllers belong to each implementation branch.
Route::prefix('research')->name('research.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('peak_memory')->name('login.store');
    });

    Route::middleware(['peak_memory', 'auth', 'api_role:admin'])->group(function () {
        Route::get('/courses', [CourseController::class, 'index'])->name('courses');
        Route::get('/courses/{id}/enrollments', [CourseEnrollmentController::class, 'index'])->whereNumber('id')->name('enrollments');
        Route::get('/reports/courses', [CourseReportController::class, 'index'])->name('reports.courses');
    });

    Route::middleware(['peak_memory', 'auth', 'api_role:student'])->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'show'])->name('dashboard');
        Route::get('/courses/{id}/materials', [CourseMaterialController::class, 'index'])->whereNumber('id')->name('materials');
        Route::get('/exams/{id}', [ExamController::class, 'show'])->whereNumber('id')->name('exams.show');
        Route::post('/exams/{id}/submissions', [ExamSubmissionController::class, 'store'])->whereNumber('id')->name('exams.submit');
    });
});
