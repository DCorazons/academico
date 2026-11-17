<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CourseServiceInterface
{
    /** Every course with its period and teacher, 20 per page, ordered by id. */
    public function paginate(): LengthAwarePaginator;
}
