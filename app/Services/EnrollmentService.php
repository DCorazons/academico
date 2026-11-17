<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function listForCourse(int $courseId): Collection
    {
        if (! Course::query()->whereKey($courseId)->exists()) {
            throw (new ModelNotFoundException)->setModel(Course::class, [$courseId]);
        }

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
}
