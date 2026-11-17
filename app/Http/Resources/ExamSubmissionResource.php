<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** POST /exams/{id}/submissions, sent with status 201 by the controller */
class ExamSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'correct_count' => $this->correct_count,
            'score' => $this->score,
        ];
    }
}
