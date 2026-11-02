<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CourseReportServiceInterface
{
    public function forPeriod(int $periodId): Collection;
}
