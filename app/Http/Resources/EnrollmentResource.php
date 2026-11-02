<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => [
                'id' => $this->student->id,
                'firstname' => $this->student->user->firstname,
                'lastname' => $this->student->user->lastname,
            ],
            'course' => [
                'id' => $this->course->id,
                'name' => $this->course->name,
            ],
            'status' => [
                'id' => $this->enrollmentStatus->id,
                'name' => $this->enrollmentStatus->name,
            ],
        ];
    }
}
