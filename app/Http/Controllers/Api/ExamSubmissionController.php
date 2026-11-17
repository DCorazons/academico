<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamSubmissionRequest;
use App\Http\Resources\ExamSubmissionResource;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExamSubmissionController extends Controller
{
    use QueriesResearchData;

    private const ALREADY_SUBMITTED = 'The exam has already been submitted.';

    private const ANSWERS_MISMATCH = 'Every question of the exam must be answered exactly once.';

    public function store(ExamSubmissionRequest $request, int $id): JsonResponse
    {
        $studentId = $request->user()->getAuthIdentifier();
        $answers = $request->answers();

        $exam = $this->findExamOrFail($id);
        $this->ensureEnrolled($exam->course_id, $studentId);

        $alreadySubmitted = DB::table('exam_submissions')
            ->where('exam_id', $id)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadySubmitted) {
            throw ValidationException::withMessages(['exam' => self::ALREADY_SUBMITTED]);
        }

        $answerKeys = DB::table('exam_questions')->where('exam_id', $id)->orderBy('id')->pluck('correct_option', 'id')->all();
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

        $submission = (object) [
            'exam_id' => $id,
            'student_id' => $studentId,
            'correct_count' => $correctCount,
            'score' => intdiv($correctCount * 100, count($answerKeys)),
        ];
        $now = now();

        try {
            $submission->id = DB::transaction(function () use ($submission, $rows, $now) {
                $submissionId = DB::table('exam_submissions')->insertGetId([
                    'exam_id' => $submission->exam_id,
                    'student_id' => $submission->student_id,
                    'correct_count' => $submission->correct_count,
                    'score' => $submission->score,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                DB::table('exam_answers')->insert(array_map(
                    fn (array $row) => ['exam_submission_id' => $submissionId, ...$row],
                    $rows,
                ));

                return $submissionId;
            });
        } catch (UniqueConstraintViolationException) {
            // Another request of the same student stored the exam first
            throw ValidationException::withMessages(['exam' => self::ALREADY_SUBMITTED]);
        }

        return (new ExamSubmissionResource($submission))->response()->setStatusCode(201);
    }
}
