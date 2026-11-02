<?php

namespace App\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Services\Contracts\CourseReportServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class CourseReportService implements CourseReportServiceInterface
{
    public function __construct(private CourseRepositoryInterface $courses) {}

    public function forPeriod(int $periodId): Collection
    {
        return $this->courses->withEnrollmentCountForPeriod($periodId);
    }
}
