<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSubmission>
 */
class ExamSubmissionFactory extends Factory
{
    protected $model = ExamSubmission::class;

    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'student_id' => Student::factory(),
            'correct_count' => 0,
            'score' => 0,
        ];
    }
}
