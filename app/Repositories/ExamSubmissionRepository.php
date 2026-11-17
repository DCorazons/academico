<?php

namespace App\Repositories;

use App\Contracts\ExamSubmissionRepositoryInterface;
use App\Models\ExamSubmission;

class ExamSubmissionRepository implements ExamSubmissionRepositoryInterface
{
    public function exists(int $examId, int $studentId): bool
    {
        return ExamSubmission::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->exists();
    }

    public function create(array $attributes): ExamSubmission
    {
        return ExamSubmission::query()->create($attributes);
    }
}
