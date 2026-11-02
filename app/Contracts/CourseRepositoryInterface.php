<?php

namespace App\Contracts;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CourseRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator;

    public function findOrFail(int $id): Course;

    public function withEnrollmentCountForPeriod(int $periodId): Collection;
}
