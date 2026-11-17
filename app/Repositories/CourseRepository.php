<?php

namespace App\Repositories;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository implements CourseRepositoryInterface
{
    public function paginateWithPeriodAndTeacher(int $perPage): LengthAwarePaginator
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->with(['period', 'teacher'])
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function exists(int $id): bool
    {
        return Course::query()->whereKey($id)->exists();
    }

    public function enrolledByStudentWithMaterialCountAndExams(int $studentId): Collection
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->whereIn('id', Enrollment::query()->select('course_id')->where('student_id', $studentId))
            ->withCount('materials')
            ->with([
                'exams' => fn ($query) => $query
                    ->withExists(['submissions as submitted' => fn ($query) => $query->where('student_id', $studentId)])
                    ->orderBy('id'),
            ])
            ->orderBy('id')
            ->get();
    }

    public function forPeriodWithEnrollmentCount(int $periodId): Collection
    {
        // The enrollments relation counts the pending and paid statuses only
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->withCount('enrollments')
            ->where('period_id', $periodId)
            ->orderBy('id')
            ->get();
    }
}
