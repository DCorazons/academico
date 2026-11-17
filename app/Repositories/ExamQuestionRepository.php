<?php

namespace App\Repositories;

use App\Contracts\ExamQuestionRepositoryInterface;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Collection;

class ExamQuestionRepository implements ExamQuestionRepositoryInterface
{
    public function forExam(int $examId): Collection
    {
        return ExamQuestion::query()
            ->where('exam_id', $examId)
            ->orderBy('id')
            ->get();
    }
}
