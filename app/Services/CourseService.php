<?php

namespace App\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Services\Contracts\CourseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService implements CourseServiceInterface
{
    private const PER_PAGE = 20;

    public function __construct(private CourseRepositoryInterface $courses) {}

    public function paginate(): LengthAwarePaginator
    {
        return $this->courses->paginate(self::PER_PAGE);
    }

    public function find(int $id): Course
    {
        return $this->courses->findOrFail($id);
    }
}
