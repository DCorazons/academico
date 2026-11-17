<?php

namespace App\Repositories;

use App\Contracts\MaterialRepositoryInterface;
use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

class MaterialRepository implements MaterialRepositoryInterface
{
    public function forCourse(int $courseId): Collection
    {
        return Material::query()
            ->where('course_id', $courseId)
            ->orderBy('id')
            ->get();
    }
}
