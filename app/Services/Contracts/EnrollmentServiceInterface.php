<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface EnrollmentServiceInterface
{
    public function listForStudent(int $studentId): Collection;
}
