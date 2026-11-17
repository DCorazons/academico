<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** GET /me/dashboard: one exam of a course and whether the student submitted it */
class DashboardExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'submitted' => (bool) $this->submitted,
        ];
    }
}
