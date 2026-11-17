<?php

namespace App\Services\Contracts;

use App\Models\Exam;
use App\Models\ExamSubmission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

interface ExamServiceInterface
{
    /**
     * The exam with its questions ordered by id.
     *
     * @throws ModelNotFoundException when the exam does not exist
     * @throws AuthorizationException when the student is not enrolled in the exam's course
     */
    public function showForStudent(int $examId, int $studentId): Exam;

    /**
     * Grades the answers and stores the submission with its answers. Every
     * question of the exam must be answered exactly once.
     *
     * @param  array<int, array{question_id: int, option: string}>  $answers
     *
     * @throws ModelNotFoundException when the exam does not exist
     * @throws AuthorizationException when the student is not enrolled in the exam's course
     * @throws ValidationException when the exam was already submitted by the student,
     *                             or when the answers do not match the exam's questions
     */
    public function submit(int $examId, int $studentId, array $answers): ExamSubmission;
}
