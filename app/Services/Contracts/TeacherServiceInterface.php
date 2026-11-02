<?php

namespace App\Services\Contracts;

use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeacherServiceInterface
{
    public function paginate(): LengthAwarePaginator;

    public function find(int $id): Teacher;
}
