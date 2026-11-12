<?php

namespace App\Services;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\StudentRepositoryInterface;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private EnrollmentRepositoryInterface $enrollments,
        private StudentRepositoryInterface $students,
    ) {}

    public function listForStudent(int $studentId): Collection
    {
        $this->students->findOrFail($studentId);

        return $this->enrollments->forStudent($studentId);
    }
}
