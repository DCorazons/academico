<?php

namespace App\Services;

use App\Contracts\TeacherRepositoryInterface;
use App\Models\Teacher;
use App\Services\Contracts\TeacherServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TeacherService implements TeacherServiceInterface
{
    private const PER_PAGE = 20;

    public function __construct(private TeacherRepositoryInterface $teachers) {}

    public function paginate(): LengthAwarePaginator
    {
        return $this->teachers->paginate(self::PER_PAGE);
    }

    public function find(int $id): Teacher
    {
        return $this->teachers->findOrFail($id);
    }
}
