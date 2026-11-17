<?php

namespace App\Contracts;

use App\Models\ExamSubmission;
use Illuminate\Database\UniqueConstraintViolationException;

interface ExamSubmissionRepositoryInterface
{
    public function exists(int $examId, int $studentId): bool;

    /**
     * @param  array{exam_id: int, student_id: int, correct_count: int, score: int}  $attributes
     *
     * @throws UniqueConstraintViolationException when the pair is already stored
     */
    public function create(array $attributes): ExamSubmission;
}
