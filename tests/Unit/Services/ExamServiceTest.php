<?php

namespace Tests\Unit\Services;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\ExamAnswerRepositoryInterface;
use App\Contracts\ExamQuestionRepositoryInterface;
use App\Contracts\ExamRepositoryInterface;
use App\Contracts\ExamSubmissionRepositoryInterface;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSubmission;
use App\Services\ExamService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PDOException;
use Tests\TestCase;

class ExamServiceTest extends TestCase
{
    /** Answer keys of the four questions of the test exam */
    private const KEYS = ['A', 'B', 'C', 'D'];

    private const STUDENT_ID = 35;

    private const OUTSIDER_ID = 36;

    private const COURSE_ID = 5;

    private const EXAM_ID = 7;

    private MockInterface $enrollments;

    private MockInterface $exams;

    private MockInterface $questions;

    private MockInterface $submissions;

    private MockInterface $answerRepository;

    /** @var array<int, array<string, mixed>> attributes given to the submission repository */
    private array $created = [];

    /** @var array<int, array<string, mixed>> rows given to the answer repository */
    private array $inserted = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->enrollments = Mockery::mock(EnrollmentRepositoryInterface::class);
        $this->enrollments->shouldReceive('isEnrolled')->with(self::COURSE_ID, self::STUDENT_ID)->andReturn(true);
        $this->enrollments->shouldReceive('isEnrolled')->with(self::COURSE_ID, self::OUTSIDER_ID)->andReturn(false);

        $this->exams = Mockery::mock(ExamRepositoryInterface::class);
        $this->exams->shouldReceive('findOrFail')->with(self::EXAM_ID)->andReturn($this->exam());
        $this->exams->shouldReceive('findOrFail')->with(999999)
            ->andThrow((new ModelNotFoundException)->setModel(Exam::class, [999999]));

        $this->questions = Mockery::mock(ExamQuestionRepositoryInterface::class);
        $this->questions->shouldReceive('forExam')->with(self::EXAM_ID)->andReturn($this->examQuestions());

        $this->submissions = Mockery::mock(ExamSubmissionRepositoryInterface::class);
        $this->submissions->shouldReceive('create')->andReturnUsing(function (array $attributes) {
            $this->created[] = $attributes;

            return (new ExamSubmission)->forceFill(['id' => 10, ...$attributes]);
        })->byDefault();

        $this->answerRepository = Mockery::mock(ExamAnswerRepositoryInterface::class);
        $this->answerRepository->shouldReceive('insert')->andReturnUsing(function (array $rows) {
            array_push($this->inserted, ...$rows);
        });

        // No database: the transaction only runs its callback
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_show_for_student_returns_the_exam_with_its_questions_ordered_by_id(): void
    {
        $exam = $this->exam();
        $questions = $this->examQuestions();
        $this->exams->shouldReceive('loadQuestions')->once()
            ->andReturn($exam->setRelation('questions', $questions));
        $service = $this->service();

        $result = $service->showForStudent(self::EXAM_ID, self::STUDENT_ID);

        $this->assertSame($exam->id, $result->id);
        $this->assertTrue($result->relationLoaded('questions'));
        $this->assertSame($questions->pluck('id')->all(), $result->questions->pluck('id')->all());
    }

    public function test_show_for_student_fails_for_an_unknown_exam(): void
    {
        $service = $this->service();

        $this->expectException(ModelNotFoundException::class);

        $service->showForStudent(999999, self::STUDENT_ID);
    }

    public function test_show_for_student_denies_a_student_not_enrolled_in_the_course(): void
    {
        $this->exams->shouldNotReceive('loadQuestions');
        $service = $this->service();

        $this->expectException(AuthorizationException::class);

        $service->showForStudent(self::EXAM_ID, self::OUTSIDER_ID);
    }

    public function test_submit_grades_and_stores_the_submission(): void
    {
        $this->submissions->shouldReceive('exists')->once()->with(self::EXAM_ID, self::STUDENT_ID)->andReturn(false);
        $questions = $this->examQuestions();
        // Three correct answers out of four
        $answers = $this->answers($questions, ['A', 'B', 'C', 'A']);
        $service = $this->service();

        $submission = $service->submit(self::EXAM_ID, self::STUDENT_ID, $answers);

        $this->assertSame(3, $submission->correct_count);
        $this->assertSame(75, $submission->score);
        $this->assertSame(
            [['exam_id' => self::EXAM_ID, 'student_id' => self::STUDENT_ID, 'correct_count' => 3, 'score' => 75]],
            $this->created,
        );
        $this->assertSame(
            [[$questions[0]->id, 'A', true], [$questions[1]->id, 'B', true], [$questions[2]->id, 'C', true], [$questions[3]->id, 'A', false]],
            array_map(fn (array $row) => [$row['exam_question_id'], $row['selected_option'], $row['is_correct']], $this->insertedFor($submission->id)),
        );
    }

    public function test_submit_fails_for_an_unknown_exam(): void
    {
        $service = $this->service();

        $this->expectException(ModelNotFoundException::class);

        $service->submit(999999, self::STUDENT_ID, []);
    }

    public function test_submit_denies_a_student_not_enrolled_in_the_course(): void
    {
        $service = $this->service();

        $this->expectException(AuthorizationException::class);

        $service->submit(self::EXAM_ID, self::OUTSIDER_ID, $this->answers($this->examQuestions(), self::KEYS));
    }

    public function test_submit_rejects_a_second_submission(): void
    {
        $this->submissions->shouldReceive('exists')->once()->with(self::EXAM_ID, self::STUDENT_ID)->andReturn(true);
        $service = $this->service();

        $this->assertValidationError('exam', fn () => $service->submit(self::EXAM_ID, self::STUDENT_ID, $this->answers($this->examQuestions(), self::KEYS)));
        $this->assertSame(0, count($this->created));
    }

    public function test_submit_rejects_a_submission_stored_concurrently(): void
    {
        $this->submissions->shouldReceive('exists')->once()->with(self::EXAM_ID, self::STUDENT_ID)->andReturn(false);
        // Another request stores the same pair between the check and the insert
        $this->submissions->shouldReceive('create')->once()
            ->andThrow(new UniqueConstraintViolationException('mariadb', 'insert into `exam_submissions`', [], new PDOException('Duplicate entry')));
        $service = $this->service();

        $this->assertValidationError('exam', fn () => $service->submit(self::EXAM_ID, self::STUDENT_ID, $this->answers($this->examQuestions(), self::KEYS)));
        $this->assertSame(0, count($this->inserted));
    }

    public function test_submit_rejects_answers_that_do_not_cover_every_question(): void
    {
        $this->submissions->shouldReceive('exists')->once()->with(self::EXAM_ID, self::STUDENT_ID)->andReturn(false);
        $service = $this->service();

        $this->assertValidationError('answers', fn () => $service->submit(self::EXAM_ID, self::STUDENT_ID, $this->answers($this->examQuestions()->take(3), ['A', 'B', 'C'])));
        $this->assertSame(0, count($this->created));
    }

    public function test_submit_rejects_answers_to_questions_of_another_exam(): void
    {
        $this->submissions->shouldReceive('exists')->once()->with(self::EXAM_ID, self::STUDENT_ID)->andReturn(false);
        $answers = $this->answers($this->examQuestions()->take(3), ['A', 'B', 'C']);
        $answers[] = ['question_id' => 999, 'option' => 'D'];
        $service = $this->service();

        $this->assertValidationError('answers', fn () => $service->submit(self::EXAM_ID, self::STUDENT_ID, $answers));
        $this->assertSame(0, count($this->created));
    }

    private function service(): ExamService
    {
        return new ExamService($this->enrollments, $this->exams, $this->questions, $this->submissions, $this->answerRepository);
    }

    private function exam(): Exam
    {
        return (new Exam)->forceFill(['id' => self::EXAM_ID, 'course_id' => self::COURSE_ID]);
    }

    private function examQuestions(): Collection
    {
        return new Collection(array_map(fn (string $key, int $index) => (new ExamQuestion)->forceFill([
            'id' => 100 + $index,
            'exam_id' => self::EXAM_ID,
            'number' => $index + 1,
            'correct_option' => $key,
        ]), self::KEYS, array_keys(self::KEYS)));
    }

    private function answers($questions, array $options): array
    {
        return $questions->values()->map(fn (ExamQuestion $question, int $index) => [
            'question_id' => $question->id,
            'option' => $options[$index],
        ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function insertedFor(int $submissionId): array
    {
        return array_values(array_filter($this->inserted, fn (array $row) => $row['exam_submission_id'] === $submissionId));
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
