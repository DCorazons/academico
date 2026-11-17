<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CourseReportServiceInterface
{
    /**
     * Every course of the period with enrollments_count, which counts the
     * enrollments with status 1 (pending) or 2 (paid), ordered by id.
     */
    public function forPeriod(int $periodId): Collection;
}
