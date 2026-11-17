<?php

namespace App\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Contracts\EnrollmentRepositoryInterface;
use App\Models\Course;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EnrollmentService implements EnrollmentServiceInterface
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private EnrollmentRepositoryInterface $enrollments,
    ) {}

    public function listForCourse(int $courseId): Collection
    {
        if (! $this->courses->exists($courseId)) {
            throw (new ModelNotFoundException)->setModel(Course::class, [$courseId]);
        }

        return $this->enrollments->forCourseWithStudentAndStatus($courseId);
    }
}
