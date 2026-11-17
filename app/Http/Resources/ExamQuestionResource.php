<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** GET /exams/{id}: one question with its options, never the answer key */
class ExamQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'question' => $this->question,
            'options' => [
                'A' => $this->option_a,
                'B' => $this->option_b,
                'C' => $this->option_c,
                'D' => $this->option_d,
            ],
        ];
    }
}
