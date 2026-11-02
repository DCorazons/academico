<?php

namespace App\Services;

use App\Models\Course;
use App\Services\Contracts\CourseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService implements CourseServiceInterface
{
    private const PER_PAGE = 20;

    public function paginate(): LengthAwarePaginator
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->with(['period', 'teacher'])
            ->orderBy('id')
            ->paginate(self::PER_PAGE);
    }

    public function find(int $id): Course
    {
        return Course::query()
            ->without(['times', 'evaluationType'])
            ->with(['period', 'teacher'])
            ->findOrFail($id);
    }
}
