<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface MaterialRepositoryInterface
{
    public function forCourse(int $courseId): Collection;
}
