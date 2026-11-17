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
                // JSON column read by HasFallbackTranslations, which ignores the
                // application locale and takes the first of fr, es, en, then any value.
                // ReferenceDataSeeder stores the fr text only, for example "PAYÉ".
                'name' => $this->enrollmentStatus->name,
            ],
        ];
    }
}
