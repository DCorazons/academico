<?php

namespace App\Console\Commands;

use Database\Seeders\ResearchSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyResearchDataset extends Command
{
    protected $signature = 'research:verify-dataset';

    protected $description = 'Check that the database holds the research dataset produced by ResearchSeeder';

    public function handle(): int
    {
        $checks = [
            ...$this->tableCounts(),
            ...$this->userChecks(),
            ...$this->enrollmentChecks(),
            ...$this->courseChecks(),
            ...$this->materialChecks(),
            ...$this->examChecks(),
        ];

        $rows = [];
        $failed = false;

        foreach ($checks as $name => [$expected, $actual]) {
            $passed = $expected === $actual;
            $failed = $failed || ! $passed;
            $rows[] = [$name, $expected, $actual, $passed ? 'OK' : 'FAIL'];
        }

        $this->table(['Check', 'Expected', 'Actual', 'Result'], $rows);

        if ($failed) {
            $this->error('The dataset does not match the research dataset.');

            return self::FAILURE;
        }

        $this->info('The dataset matches the research dataset.');

        return self::SUCCESS;
    }

    /** @return array<string, array{0: int, 1: int}> */
    private function tableCounts(): array
    {
        $students = ResearchSeeder::STUDENTS;
        $teachers = ResearchSeeder::TEACHERS;
        $courses = ResearchSeeder::COURSES;
        $exams = $courses * count(ResearchSeeder::EXAM_TYPES);

        return [
            'years' => [1, DB::table('years')->count()],
            'periods' => [1, DB::table('periods')->count()],
            'rhythms' => [1, DB::table('rhythms')->count()],
            'levels' => [ResearchSeeder::SEMESTERS, DB::table('levels')->count()],
            'users' => [$students + $teachers + 1, DB::table('users')->count()],
            'students' => [$students, DB::table('students')->count()],
            'teachers' => [$teachers, DB::table('teachers')->count()],
            'courses' => [$courses, DB::table('courses')->count()],
            'enrollments' => [$students * ResearchSeeder::COURSES_PER_STUDENT, DB::table('enrollments')->count()],
            'materials' => [$courses * ResearchSeeder::MATERIALS_PER_COURSE, DB::table('materials')->count()],
            'exams' => [$exams, DB::table('exams')->count()],
            'exam questions' => [$exams * ResearchSeeder::QUESTIONS_PER_EXAM, DB::table('exam_questions')->count()],
            'exam submissions' => [0, DB::table('exam_submissions')->count()],
            'exam answers' => [0, DB::table('exam_answers')->count()],
        ];
    }

    /** @return array<string, array{0: int, 1: int}> */
    private function userChecks(): array
    {
        $studentIds = DB::table('students')->select('id');

        return [
            'admin with research token' => [1, DB::table('users')
                ->where('id', ResearchSeeder::ADMIN_ID)
                ->where('api_token', config('research.api_token'))
                ->count()],
            'students with a token' => [ResearchSeeder::STUDENTS, DB::table('users')
                ->whereIn('id', $studentIds)
                ->whereNotNull('api_token')
                ->count()],
            'distinct student tokens' => [ResearchSeeder::STUDENTS, DB::table('users')
                ->whereIn('id', $studentIds)
                ->distinct()
                ->count('api_token')],
            'teachers with a token' => [0, DB::table('users')
                ->whereIn('id', DB::table('teachers')->select('id'))
                ->whereNotNull('api_token')
                ->count()],
        ];
    }

    /** @return array<string, array{0: int, 1: int}> */
    private function enrollmentChecks(): array
    {
        $coursesPerStudent = DB::table('enrollments')
            ->selectRaw('student_id, COUNT(*) as total, COUNT(DISTINCT course_id) as distinct_courses')
            ->groupBy('student_id')
            ->get();

        return [
            'enrollments not paid' => [0, DB::table('enrollments')->where('status_id', '!=', ResearchSeeder::PAID_STATUS_ID)->count()],
            'students with 7 distinct courses' => [
                ResearchSeeder::STUDENTS,
                $coursesPerStudent->filter(fn ($row) => (int) $row->total === ResearchSeeder::COURSES_PER_STUDENT
                    && (int) $row->distinct_courses === ResearchSeeder::COURSES_PER_STUDENT)->count(),
            ],
        ];
    }

    /** @return array<string, array{0: int, 1: int}> */
    private function courseChecks(): array
    {
        $coursesPerTeacher = DB::table('courses')->selectRaw('teacher_id, COUNT(*) as total')->groupBy('teacher_id')->pluck('total');
        $coursesPerLevel = DB::table('courses')->selectRaw('level_id, COUNT(*) as total')->groupBy('level_id')->pluck('total');

        return [
            'teachers with 6 courses' => [26, $coursesPerTeacher->filter(fn ($total) => (int) $total === 6)->count()],
            'teachers with 5 courses' => [7, $coursesPerTeacher->filter(fn ($total) => (int) $total === 5)->count()],
            'levels with courses' => [count(ResearchSeeder::ACTIVE_SEMESTERS), $coursesPerLevel->count()],
            'levels with 47 or 48 courses' => [
                count(ResearchSeeder::ACTIVE_SEMESTERS),
                $coursesPerLevel->filter(fn ($total) => in_array((int) $total, [47, 48], true))->count(),
            ],
        ];
    }

    /** @return array<string, array{0: int, 1: int}> */
    private function materialChecks(): array
    {
        $perCourse = ResearchSeeder::MATERIALS_PER_COURSE;

        $coursesWithAllMeetings = DB::table('materials')
            ->selectRaw('course_id, COUNT(*) as total, COUNT(DISTINCT meeting) as meetings, MIN(meeting) as first, MAX(meeting) as last')
            ->groupBy('course_id')
            ->get()
            ->filter(fn ($row) => (int) $row->total === $perCourse
                && (int) $row->meetings === $perCourse
                && (int) $row->first === 1
                && (int) $row->last === $perCourse)
            ->count();

        return [
            'courses with meetings 1-14' => [ResearchSeeder::COURSES, $coursesWithAllMeetings],
        ];
    }

    /** @return array<string, array{0: int, 1: int}> */
    private function examChecks(): array
    {
        $types = ResearchSeeder::EXAM_TYPES;
        $perExam = ResearchSeeder::QUESTIONS_PER_EXAM;

        $coursesWithBothExams = DB::table('exams')
            ->selectRaw('course_id, COUNT(*) as total, COUNT(DISTINCT type) as types')
            ->whereIn('type', $types)
            ->groupBy('course_id')
            ->get()
            ->filter(fn ($row) => (int) $row->total === count($types) && (int) $row->types === count($types))
            ->count();

        $examsWithAllQuestions = DB::table('exam_questions')
            ->selectRaw('exam_id, COUNT(*) as total, COUNT(DISTINCT number) as numbers, MIN(number) as first, MAX(number) as last')
            ->groupBy('exam_id')
            ->get()
            ->filter(fn ($row) => (int) $row->total === $perExam
                && (int) $row->numbers === $perExam
                && (int) $row->first === 1
                && (int) $row->last === $perExam)
            ->count();

        $openPairs = DB::table('enrollments')
            ->join('exams', 'exams.course_id', '=', 'enrollments.course_id')
            ->leftJoin('exam_submissions', function ($join) {
                $join->on('exam_submissions.student_id', '=', 'enrollments.student_id')
                    ->on('exam_submissions.exam_id', '=', 'exams.id');
            })
            ->whereNull('exam_submissions.id')
            ->count();

        return [
            'courses with UTS and UAS' => [ResearchSeeder::COURSES, $coursesWithBothExams],
            'exams with questions 1-25' => [ResearchSeeder::COURSES * count($types), $examsWithAllQuestions],
            'questions with invalid answer key' => [0, DB::table('exam_questions')->whereNotIn('correct_option', ResearchSeeder::OPTIONS)->count()],
            'open student-exam pairs' => [
                ResearchSeeder::STUDENTS * ResearchSeeder::COURSES_PER_STUDENT * count($types),
                $openPairs,
            ],
        ];
    }
}
