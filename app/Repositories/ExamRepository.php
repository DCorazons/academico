<?php

namespace App\Repositories;

use App\Contracts\ExamRepositoryInterface;
use App\Models\Exam;

class ExamRepository implements ExamRepositoryInterface
{
    public function findOrFail(int $id): Exam
    {
        return Exam::query()->findOrFail($id);
    }

    public function loadQuestions(Exam $exam): Exam
    {
        return $exam->load(['questions' => fn ($query) => $query->orderBy('id')]);
    }
}
