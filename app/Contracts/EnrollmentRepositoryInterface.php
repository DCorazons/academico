<?php

namespace App\Contracts;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

interface EnrollmentRepositoryInterface
{
    public function forStudent(int $studentId): Collection;

    public function create(array $attributes): Enrollment;
}
