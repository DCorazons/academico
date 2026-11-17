<?php

namespace App\Repositories;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function forCourseWithStudentAndStatus(int $courseId): Collection
    {
        return Enrollment::query()
            ->without(['student', 'course', 'childrenEnrollments'])
            ->with([
                'student' => fn ($query) => $query->without(['phone', 'institution', 'profession']),
                'enrollmentStatus',
            ])
            ->where('course_id', $courseId)
            ->orderBy('id')
            ->get();
    }

    public function isEnrolled(int $courseId, int $studentId): bool
    {
        return Enrollment::query()
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();
    }
}
