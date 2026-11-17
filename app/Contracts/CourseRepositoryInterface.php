<?php

namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface
{
    public function paginateWithPeriodAndTeacher(int $perPage): LengthAwarePaginator;

    public function exists(int $id): bool;

    public function enrolledByStudentWithMaterialCountAndExams(int $studentId): Collection;

    public function forPeriodWithEnrollmentCount(int $periodId): Collection;
}
