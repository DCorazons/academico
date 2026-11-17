<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    protected $model = ExamQuestion::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'number' => fake()->numberBetween(1, 25),
            'question' => fake()->sentence(10),
            'option_a' => fake()->sentence(3),
            'option_b' => fake()->sentence(3),
            'option_c' => fake()->sentence(3),
            'option_d' => fake()->sentence(3),
            'correct_option' => fake()->randomElement(['A', 'B', 'C', 'D']),
        ];
    }
}
