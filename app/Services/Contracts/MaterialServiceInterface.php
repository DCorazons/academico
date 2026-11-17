<?php

namespace App\Services\Contracts;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface MaterialServiceInterface
{
    /**
     * Every material of the course, ordered by id.
     *
     * @throws ModelNotFoundException when the course does not exist
     * @throws AuthorizationException when the student is not enrolled in the course
     */
    public function listForStudent(int $courseId, int $studentId): Collection;
}
