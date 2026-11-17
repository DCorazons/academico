<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface DashboardServiceInterface
{
    /**
     * The courses the student is enrolled in, ordered by id, each with
     * materials_count and its exams ordered by id, each exam with a submitted
     * flag for this student. No score is loaded.
     */
    public function forStudent(int $studentId): Collection;
}
