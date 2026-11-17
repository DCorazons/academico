<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Checks the shape of the answers without any query. Whether the questions
 * belong to the exam and whether every question is answered is checked by
 * the exam service, which already loads the questions to grade them.
 */
class ExamSubmissionRequest extends FormRequest
{
    public const OPTIONS = ['A', 'B', 'C', 'D'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'array:question_id,option'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.option' => ['required', 'string', 'in:'.implode(',', self::OPTIONS)],
        ];
    }

    /** @return array<int, array{question_id: int, option: string}> */
    public function answers(): array
    {
        return array_map(fn (array $answer) => [
            'question_id' => (int) $answer['question_id'],
            'option' => $answer['option'],
        ], array_values($this->validated('answers')));
    }
}
