<?php

namespace Database\Factories;

use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamAnswer>
 */
class ExamAnswerFactory extends Factory
{
    protected $model = ExamAnswer::class;

    public function definition(): array
    {
        return [
            'exam_submission_id' => ExamSubmission::factory(),
            'exam_question_id' => ExamQuestion::factory(),
            'selected_option' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'is_correct' => false,
        ];
    }
}
