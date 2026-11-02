<?php

namespace App\Contracts;

use App\Models\Student;

interface StudentRepositoryInterface
{
    public function findOrFail(int $id): Student;
}
