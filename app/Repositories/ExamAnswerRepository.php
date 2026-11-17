<?php

namespace App\Repositories;

use App\Contracts\ExamAnswerRepositoryInterface;
use App\Models\ExamAnswer;

class ExamAnswerRepository implements ExamAnswerRepositoryInterface
{
    public function insert(array $rows): void
    {
        ExamAnswer::query()->insert($rows);
    }
}
