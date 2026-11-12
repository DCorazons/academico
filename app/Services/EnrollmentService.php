<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function listForStudent(int $studentId): Collection
    {
        Student::query()
            ->without(['user', 'phone', 'institution', 'profession'])
            ->findOrFail($studentId);

        return Enrollment::query()
            ->without('childrenEnrollments')
            ->with([
                'student' => fn ($query) => $query->without(['phone', 'institution', 'profession']),
                'course' => fn ($query) => $query->without(['times', 'evaluationType']),
                'enrollmentStatus',
            ])
            ->where('student_id', $studentId)
            ->orderBy('id')
            ->get();
    }
}
