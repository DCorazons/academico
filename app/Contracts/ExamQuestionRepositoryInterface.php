<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ExamQuestionRepositoryInterface
{
    public function forExam(int $examId): Collection;
}
