<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Data access of the query builder implementation, shared by its API and web
 * controllers. It reproduces what the Eloquent relations of the other implementations
 * do implicitly (fallback translation of the status name, the status filter of the
 * report count, the soft delete filter on student users), and throws the same
 * exceptions, so that the responses match.
 */
trait QueriesResearchData
{
    private const PER_PAGE = 20;

    /** Locale order of App\Models\Concerns\HasFallbackTranslations */
    private const LOCALE_PRIORITY = ['fr', 'es', 'en'];

    /** Same statuses as the enrollments relation of App\Models\Course (pending, paid) */
    private const COUNTED_STATUSES = ['1', '2'];

    private const ALREADY_SUBMITTED = 'The exam has already been submitted.';

    private const ANSWERS_MISMATCH = 'Every question of the exam must be answered exactly once.';

    private function coursePage(): LengthAwarePaginator
    {
        $page = DB::table('courses')->orderBy('id')->paginate(self::PER_PAGE);
        $courses = collect($page->items());

        $periods = $this->keyedById('periods', $courses->pluck('period_id'));
        $teachers = $this->keyedById('teachers', $courses->pluck('teacher_id'));
        $users = $this->keyedById('users', $teachers->keys());

        foreach ($courses as $course) {
            $course->period = $periods[$course->period_id];
            $course->teacher = $teachers[$course->teacher_id];
            $course->teacher->user = $users[$course->teacher_id];
        }

        return $page;
    }

    private function enrollmentsForCourse(int $courseId): Collection
    {
        $this->ensureCourseExists($courseId);

        $enrollments = DB::table('enrollments')->where('course_id', $courseId)->orderBy('id')->get();

        $students = $this->keyedById('students', $enrollments->pluck('student_id'));
        // Student::user excludes soft deleted users (Teacher::user does not)
        $users = DB::table('users')->whereIn('id', $students->keys())->whereNull('deleted_at')->get()->keyBy('id');
        $statuses = $this->keyedById('enrollment_status_types', $enrollments->pluck('status_id'));

        foreach ($enrollments as $enrollment) {
            $student = clone $students[$enrollment->student_id];
            $student->user = $users[$enrollment->student_id];
            $status = clone $statuses[$enrollment->status_id];
            $status->name = $this->translated($status->name);

            $enrollment->student = $student;
            $enrollment->enrollmentStatus = $status;
        }

        return $enrollments;
    }

    private function dashboardForStudent(int $studentId): Collection
    {
        $courses = DB::table('courses')
            ->select('courses.*')
            ->selectSub(
                DB::table('materials')->selectRaw('count(*)')->whereColumn('courses.id', 'materials.course_id'),
                'materials_count',
            )
            ->whereIn('id', DB::table('enrollments')->select('course_id')->where('student_id', $studentId))
            ->orderBy('id')
            ->get();

        $exams = DB::table('exams')
            ->select('exams.*')
            ->selectRaw(
                'exists(select * from `exam_submissions` where `exams`.`id` = `exam_submissions`.`exam_id` and `student_id` = ?) as `submitted`',
                [$studentId],
            )
            ->whereIn('exams.course_id', $courses->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('course_id');

        foreach ($courses as $course) {
            $course->exams = $exams->get($course->id, collect())->values();
        }

        return $courses;
    }

    private function materialsForStudent(int $courseId, int $studentId): Collection
    {
        $this->ensureCourseExists($courseId);
        $this->ensureEnrolled($courseId, $studentId);

        return DB::table('materials')->where('course_id', $courseId)->orderBy('id')->get();
    }

    private function examForStudent(int $examId, int $studentId): object
    {
        $exam = $this->findExamOrFail($examId);
        $this->ensureEnrolled($exam->course_id, $studentId);

        $exam->questions = DB::table('exam_questions')->where('exam_id', $examId)->orderBy('id')->get();

        return $exam;
    }

    /** @param  array<int, array{question_id: int, option: string}>  $answers */
    private function submitExam(int $examId, int $studentId, array $answers): object
    {
        $exam = $this->findExamOrFail($examId);
        $this->ensureEnrolled($exam->course_id, $studentId);

        $alreadySubmitted = DB::table('exam_submissions')
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadySubmitted) {
            throw ValidationException::withMessages(['exam' => self::ALREADY_SUBMITTED]);
        }

        $answerKeys = DB::table('exam_questions')->where('exam_id', $examId)->orderBy('id')->pluck('correct_option', 'id')->all();
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
            'exam_id' => $examId,
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

        return $submission;
    }

    private function reportForPeriod(int $periodId): Collection
    {
        return DB::table('courses')
            ->select('courses.*')
            ->selectSub(
                DB::table('enrollments')
                    ->selectRaw('count(*)')
                    ->whereColumn('courses.id', 'enrollments.course_id')
                    ->whereIn('status_id', self::COUNTED_STATUSES),
                'enrollments_count',
            )
            ->where('period_id', $periodId)
            ->orderBy('id')
            ->get();
    }

    private function ensureCourseExists(int $courseId): void
    {
        if (! DB::table('courses')->where('id', $courseId)->exists()) {
            throw (new ModelNotFoundException)->setModel('App\Models\Course', [$courseId]);
        }
    }

    private function ensureEnrolled(int $courseId, int $studentId): void
    {
        $isEnrolled = DB::table('enrollments')
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();

        if (! $isEnrolled) {
            throw new AuthorizationException;
        }
    }

    private function findExamOrFail(int $examId): object
    {
        $exam = DB::table('exams')->where('id', $examId)->first();

        if ($exam === null) {
            throw (new ModelNotFoundException)->setModel('App\Models\Exam', [$examId]);
        }

        return $exam;
    }

    /** @return Collection<int, object> rows of a table indexed by id, for the given ids */
    private function keyedById(string $table, Collection $ids): Collection
    {
        return DB::table($table)->whereIn('id', $ids->unique()->values())->get()->keyBy('id');
    }

    /** The first non-empty text in locale order, then any non-empty text */
    private function translated(?string $json): string
    {
        $translations = json_decode((string) $json, true) ?: [];

        foreach (self::LOCALE_PRIORITY as $locale) {
            if (isset($translations[$locale]) && $translations[$locale] !== '') {
                return $translations[$locale];
            }
        }

        foreach ($translations as $value) {
            if ($value !== '' && $value !== null) {
                return $value;
            }
        }

        return '';
    }
}
