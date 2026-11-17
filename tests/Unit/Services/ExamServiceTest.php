<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSubmission;
use App\Models\Student;
use App\Services\ExamService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamServiceTest extends TestCase
{
    use RefreshDatabase;

    /** Answer keys of the four questions of the test exam */
    private const KEYS = ['A', 'B', 'C', 'D'];

    public function test_show_for_student_returns_the_exam_with_its_questions_ordered_by_id(): void
    {
        [$student, $exam, $questions] = $this->enrolledStudentAndExam();
        $service = new ExamService;

        $result = $service->showForStudent($exam->id, $student->id);

        $this->assertSame($exam->id, $result->id);
        $this->assertTrue($result->relationLoaded('questions'));
        $this->assertSame($questions->pluck('id')->all(), $result->questions->pluck('id')->all());
    }

    public function test_show_for_student_fails_for_an_unknown_exam(): void
    {
        $student = Student::factory()->create();
        $service = new ExamService;

        $this->expectException(ModelNotFoundException::class);

        $service->showForStudent(999999, $student->id);
    }

    public function test_show_for_student_denies_a_student_not_enrolled_in_the_course(): void
    {
        [, $exam] = $this->enrolledStudentAndExam();
        $outsider = Student::factory()->create();
        $service = new ExamService;

        $this->expectException(AuthorizationException::class);

        $service->showForStudent($exam->id, $outsider->id);
    }

    public function test_submit_grades_and_stores_the_submission(): void
    {
        [$student, $exam, $questions] = $this->enrolledStudentAndExam();
        // Three correct answers out of four
        $answers = $this->answers($questions, ['A', 'B', 'C', 'A']);
        $service = new ExamService;

        $submission = $service->submit($exam->id, $student->id, $answers);

        $this->assertSame(3, $submission->correct_count);
        $this->assertSame(75, $submission->score);
        $this->assertDatabaseHas('exam_submissions', [
            'id' => $submission->id, 'exam_id' => $exam->id, 'student_id' => $student->id, 'correct_count' => 3, 'score' => 75,
        ]);
        $this->assertSame(
            [[$questions[0]->id, 'A', true], [$questions[1]->id, 'B', true], [$questions[2]->id, 'C', true], [$questions[3]->id, 'A', false]],
            ExamAnswer::query()->where('exam_submission_id', $submission->id)->orderBy('id')->get()
                ->map(fn ($answer) => [$answer->exam_question_id, $answer->selected_option, $answer->is_correct])->all(),
        );
    }

    public function test_submit_fails_for_an_unknown_exam(): void
    {
        $student = Student::factory()->create();
        $service = new ExamService;

        $this->expectException(ModelNotFoundException::class);

        $service->submit(999999, $student->id, []);
    }

    public function test_submit_denies_a_student_not_enrolled_in_the_course(): void
    {
        [, $exam, $questions] = $this->enrolledStudentAndExam();
        $outsider = Student::factory()->create();
        $service = new ExamService;

        $this->expectException(AuthorizationException::class);

        $service->submit($exam->id, $outsider->id, $this->answers($questions, self::KEYS));
    }

    public function test_submit_rejects_a_second_submission(): void
    {
        [$student, $exam, $questions] = $this->enrolledStudentAndExam();
        ExamSubmission::factory()->create(['exam_id' => $exam->id, 'student_id' => $student->id]);
        $service = new ExamService;

        $this->assertValidationError('exam', fn () => $service->submit($exam->id, $student->id, $this->answers($questions, self::KEYS)));
        $this->assertSame(1, ExamSubmission::query()->count());
    }

    public function test_submit_rejects_a_submission_stored_concurrently(): void
    {
        [$student, $exam, $questions] = $this->enrolledStudentAndExam();
        // Another request stores the same pair between the check and the insert
        ExamSubmission::creating(fn () => DB::table('exam_submissions')->insert([
            'exam_id' => $exam->id, 'student_id' => $student->id, 'correct_count' => 0, 'score' => 0,
        ]));
        $service = new ExamService;

        $this->assertValidationError('exam', fn () => $service->submit($exam->id, $student->id, $this->answers($questions, self::KEYS)));
        $this->assertSame(0, ExamAnswer::query()->count());
    }

    public function test_submit_rejects_answers_that_do_not_cover_every_question(): void
    {
        [$student, $exam, $questions] = $this->enrolledStudentAndExam();
        $service = new ExamService;

        $this->assertValidationError('answers', fn () => $service->submit($exam->id, $student->id, $this->answers($questions->take(3), ['A', 'B', 'C'])));
        $this->assertSame(0, ExamSubmission::query()->count());
    }

    public function test_submit_rejects_answers_to_questions_of_another_exam(): void
    {
        [$student, $exam, $questions] = $this->enrolledStudentAndExam();
        $foreign = ExamQuestion::factory()->create();
        $answers = $this->answers($questions->take(3), ['A', 'B', 'C']);
        $answers[] = ['question_id' => $foreign->id, 'option' => 'D'];
        $service = new ExamService;

        $this->assertValidationError('answers', fn () => $service->submit($exam->id, $student->id, $answers));
        $this->assertSame(0, ExamSubmission::query()->count());
    }

    /** @return array{0: Student, 1: Exam, 2: Collection<int, ExamQuestion>} */
    private function enrolledStudentAndExam(): array
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        Enrollment::withoutEvents(fn () => Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status_id' => 2,
        ]));
        $exam = Exam::factory()->create(['course_id' => $course->id]);
        $questions = collect(self::KEYS)->map(fn (string $key, int $index) => ExamQuestion::factory()->create([
            'exam_id' => $exam->id,
            'number' => $index + 1,
            'correct_option' => $key,
        ]));

        return [$student, $exam, $questions];
    }

    private function answers($questions, array $options): array
    {
        return $questions->values()->map(fn (ExamQuestion $question, int $index) => [
            'question_id' => $question->id,
            'option' => $options[$index],
        ])->all();
    }

    private function assertValidationError(string $key, callable $call): void
    {
        try {
            $call();
            $this->fail('Expected a validation error on '.$key);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($key, $exception->errors());
        }
    }
}
