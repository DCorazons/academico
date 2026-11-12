<?php

namespace App\Repositories;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function forStudent(int $studentId): Collection
    {
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
