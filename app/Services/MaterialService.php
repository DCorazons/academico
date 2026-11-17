<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Material;
use App\Services\Contracts\MaterialServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MaterialService implements MaterialServiceInterface
{
    public function listForStudent(int $courseId, int $studentId): Collection
    {
        if (! Course::query()->whereKey($courseId)->exists()) {
            throw (new ModelNotFoundException)->setModel(Course::class, [$courseId]);
        }

        $isEnrolled = Enrollment::query()
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();

        if (! $isEnrolled) {
            throw new AuthorizationException;
        }

        return Material::query()
            ->where('course_id', $courseId)
            ->orderBy('id')
            ->get();
    }
}
