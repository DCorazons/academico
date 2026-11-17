<?php

namespace App\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\MaterialRepositoryInterface;
use App\Models\Course;
use App\Services\Contracts\MaterialServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MaterialService implements MaterialServiceInterface
{
    public function __construct(
        private CourseRepositoryInterface $courses,
        private EnrollmentRepositoryInterface $enrollments,
        private MaterialRepositoryInterface $materials,
    ) {}

    public function listForStudent(int $courseId, int $studentId): Collection
    {
        if (! $this->courses->exists($courseId)) {
            throw (new ModelNotFoundException)->setModel(Course::class, [$courseId]);
        }

        $isEnrolled = $this->enrollments->isEnrolled($courseId, $studentId);

        if (! $isEnrolled) {
            throw new AuthorizationException;
        }

        return $this->materials->forCourse($courseId);
    }
}
