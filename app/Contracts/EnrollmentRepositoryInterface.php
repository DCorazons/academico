<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface EnrollmentRepositoryInterface
{
    public function forCourseWithStudentAndStatus(int $courseId): Collection;

    public function isEnrolled(int $courseId, int $studentId): bool;
}
