<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\Contracts\DashboardServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardService implements DashboardServiceInterface
{
    public function forStudent(int $studentId): Collection
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
}
