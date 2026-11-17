<?php

namespace App\Contracts;

use App\Models\Exam;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface ExamRepositoryInterface
{
    /** @throws ModelNotFoundException */
    public function findOrFail(int $id): Exam;

    public function loadQuestions(Exam $exam): Exam;
}
