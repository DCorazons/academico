<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface EnrollmentRepositoryInterface
{
    public function forStudent(int $studentId): Collection;
}
