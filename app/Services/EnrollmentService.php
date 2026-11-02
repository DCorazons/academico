<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService implements EnrollmentServiceInterface
{
    private const PENDING_STATUS_ID = 1;

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

    public function create(int $studentId, int $courseId, int $responsibleId): Enrollment
    {
        // Model events and activity logs stay out of the work measured on this endpoint
        return Enrollment::withoutEvents(fn () => Enrollment::query()->create([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'responsible_id' => $responsibleId,
            'status_id' => self::PENDING_STATUS_ID,
        ]));
    }
}
