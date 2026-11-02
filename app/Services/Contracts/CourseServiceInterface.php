<?php

namespace App\Services\Contracts;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CourseServiceInterface
{
    public function paginate(): LengthAwarePaginator;

    public function find(int $id): Course;
}
