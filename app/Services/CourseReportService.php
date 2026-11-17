<?php

namespace App\Services;

use App\Models\Course;
use App\Services\Contracts\CourseReportServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class CourseReportService implements CourseReportServiceInterface
{
    public function forPeriod(int $periodId): Collection
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
