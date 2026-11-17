<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['UTS', 'UAS']);

        return [
            'course_id' => Course::factory(),
            'type' => $type,
            'title' => $type.' '.fake()->words(3, true),
        ];
    }
}
