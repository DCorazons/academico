<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** GET /reports/courses?period_id={id} */
class CourseReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'enrollments_count' => (int) $this->enrollments_count,
        ];
    }
}
