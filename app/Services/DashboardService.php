<?php

namespace App\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Services\Contracts\DashboardServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(private CourseRepositoryInterface $courses) {}

    public function forStudent(int $studentId): Collection
    {
        return $this->courses->enrolledByStudentWithMaterialCountAndExams($studentId);
    }
}
