<?php

namespace App\Contracts;

interface ExamAnswerRepositoryInterface
{
    /** @param  array<int, array{exam_submission_id: int, exam_question_id: int, selected_option: string, is_correct: bool}>  $rows */
    public function insert(array $rows): void;
}
