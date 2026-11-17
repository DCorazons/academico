<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface EnrollmentServiceInterface
{
    /**
     * Every enrollment of the course, whatever its status, with the student, the
     * student's user, and the status, ordered by id.
     *
     * @throws ModelNotFoundException when the course does not exist
     */
    public function listForCourse(int $courseId): Collection;
}
