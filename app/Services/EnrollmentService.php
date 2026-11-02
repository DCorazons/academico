<?php

namespace App\Services;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\StudentRepositoryInterface;
use App\Models\Enrollment;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService implements EnrollmentServiceInterface
{
    private const PENDING_STATUS_ID = 1;

    public function __construct(
        private EnrollmentRepositoryInterface $enrollments,
        private StudentRepositoryInterface $students,
    ) {}

    public function listForStudent(int $studentId): Collection
    {
        $this->students->findOrFail($studentId);

        return $this->enrollments->forStudent($studentId);
    }

    public function create(int $studentId, int $courseId, int $responsibleId): Enrollment
    {
        return $this->enrollments->create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'responsible_id' => $responsibleId,
            'status_id' => self::PENDING_STATUS_ID,
        ]);
    }
}
