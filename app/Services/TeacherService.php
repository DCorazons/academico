<?php

namespace App\Services;

use App\Models\Teacher;
use App\Services\Contracts\TeacherServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeacherService implements TeacherServiceInterface
{
    private const PER_PAGE = 20;

    public function paginate(): LengthAwarePaginator
    {
        return Teacher::query()->orderBy('id')->paginate(self::PER_PAGE);
    }

    public function find(int $id): Teacher
    {
        return Teacher::query()->findOrFail($id);
    }
}
