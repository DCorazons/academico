<?php

namespace App\Repositories;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository implements CourseRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->with(['period', 'teacher'])
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Course
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->with(['period', 'teacher'])
            ->findOrFail($id);
    }

    public function withEnrollmentCountForPeriod(int $periodId): Collection
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->withCount('enrollments')
            ->where('period_id', $periodId)
            ->orderBy('id')
            ->get();
    }
}
