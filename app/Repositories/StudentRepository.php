<?php

namespace App\Repositories;

use App\Contracts\StudentRepositoryInterface;
use App\Models\Student;

class StudentRepository implements StudentRepositoryInterface
{
    public function findOrFail(int $id): Student
    {
        return Student::query()
            ->without(['user', 'phone', 'institution', 'profession'])
            ->findOrFail($id);
    }
}
