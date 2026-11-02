<?php

namespace App\Services\Contracts;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

interface EnrollmentServiceInterface
{
    public function listForStudent(int $studentId): Collection;

    public function create(int $studentId, int $courseId, int $responsibleId): Enrollment;
}
