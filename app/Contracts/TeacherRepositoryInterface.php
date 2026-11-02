<?php

namespace App\Contracts;

use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TeacherRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator;

    public function findOrFail(int $id): Teacher;
}
