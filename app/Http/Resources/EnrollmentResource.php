<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** GET /courses/{id}/enrollments */
class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => [
                'id' => $this->student->id,
                'idnumber' => $this->student->idnumber,
                'firstname' => UserName::first($this->student->user->firstname),
                'lastname' => UserName::last($this->student->user->lastname),
            ],
            'status' => [
                'id' => $this->enrollmentStatus->id,
                // Translated column: the text for the application locale
                'name' => $this->enrollmentStatus->name,
            ],
        ];
    }
}
