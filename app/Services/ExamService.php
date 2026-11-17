<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamSubmission;
use App\Services\Contracts\ExamServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamService implements ExamServiceInterface
{
    public const ALREADY_SUBMITTED = 'The exam has already been submitted.';

    public const ANSWERS_MISMATCH = 'Every question of the exam must be answered exactly once.';

    public function showForStudent(int $examId, int $studentId): Exam
    {
        $exam = Exam::query()->findOrFail($examId);

        $this->ensureEnrolled($exam->course_id, $studentId);

        return $exam->load(['questions' => fn ($query) => $query->orderBy('id')]);
    }

    public function submit(int $examId, int $studentId, array $answers): ExamSubmission
    {
        $exam = Exam::query()->findOrFail($examId);

        $this->ensureEnrolled($exam->course_id, $studentId);

        $alreadySubmitted = ExamSubmission::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadySubmitted) {
            throw ValidationException::withMessages(['exam' => self::ALREADY_SUBMITTED]);
        }

        $questions = ExamQuestion::query()
            ->where('exam_id', $examId)
            ->orderBy('id')
            ->get();

        $grades = $this->grade($questions->pluck('correct_option', 'id')->all(), $answers);

        try {
            return DB::transaction(function () use ($examId, $studentId, $grades) {
                $submission = ExamSubmission::query()->create([
                    'exam_id' => $examId,
                    'student_id' => $studentId,
                    'correct_count' => $grades['correct_count'],
                    'score' => $grades['score'],
                ]);

                ExamAnswer::query()->insert(array_map(
                    fn (array $answer) => ['exam_submission_id' => $submission->id, ...$answer],
                    $grades['answers'],
                ));

                return $submission;
            });
        } catch (UniqueConstraintViolationException) {
            // Another request of the same student stored the exam first
            throw ValidationException::withMessages(['exam' => self::ALREADY_SUBMITTED]);
        }
    }

    private function ensureEnrolled(int $courseId, int $studentId): void
    {
        $isEnrolled = Enrollment::query()
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();

        if (! $isEnrolled) {
            throw new AuthorizationException;
        }
    }

    /**
     * Compares the answers with the answer keys. Every question must be answered
     * exactly once; the request has already checked that question ids are distinct.
     *
     * @param  array<int, string>  $answerKeys  correct option, indexed by question id
     * @param  array<int, array{question_id: int, option: string}>  $answers
     * @return array{correct_count: int, score: int, answers: array<int, array{exam_question_id: int, selected_option: string, is_correct: bool}>}
     */
    private function grade(array $answerKeys, array $answers): array
    {
        $answeredIds = array_column($answers, 'question_id');

        if (count($answeredIds) !== count($answerKeys) || array_diff($answeredIds, array_keys($answerKeys)) !== []) {
            throw ValidationException::withMessages(['answers' => self::ANSWERS_MISMATCH]);
        }

        $rows = [];
        $correctCount = 0;

        foreach ($answers as $answer) {
            $isCorrect = $answerKeys[$answer['question_id']] === $answer['option'];
            $correctCount += (int) $isCorrect;
            $rows[] = [
                'exam_question_id' => $answer['question_id'],
                'selected_option' => $answer['option'],
                'is_correct' => $isCorrect,
            ];
        }

        return [
            'correct_count' => $correctCount,
            'score' => intdiv($correctCount * 100, count($answerKeys)),
            'answers' => $rows,
        ];
    }
}
