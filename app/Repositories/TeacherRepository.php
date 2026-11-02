<?php

namespace App\Repositories;

use App\Contracts\TeacherRepositoryInterface;
use App\Models\Teacher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeacherRepository implements TeacherRepositoryInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Teacher::query()->orderBy('id')->paginate($perPage);
    }

    public function findOrFail(int $id): Teacher
    {
        return Teacher::query()->findOrFail($id);
    }
}
