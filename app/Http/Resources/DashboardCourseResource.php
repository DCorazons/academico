<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** GET /me/dashboard: one course of the student, without any score */
class DashboardCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'materials_count' => (int) $this->materials_count,
            'exams' => DashboardExamResource::collection($this->exams),
        ];
    }
}
