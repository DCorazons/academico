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
            ...$this->enrollmentChecks(),
            ...$this->courseChecks(),
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

        return [
            'years' => [1, DB::table('years')->count()],
            'periods' => [1, DB::table('periods')->count()],
            'rhythms' => [1, DB::table('rhythms')->count()],
            'levels' => [ResearchSeeder::SEMESTERS, DB::table('levels')->count()],
            'users' => [$students + $teachers + 1, DB::table('users')->count()],
            'students' => [$students, DB::table('students')->count()],
            'teachers' => [$teachers, DB::table('teachers')->count()],
            'courses' => [ResearchSeeder::COURSES, DB::table('courses')->count()],
            'enrollments' => [$students * ResearchSeeder::COURSES_PER_STUDENT, DB::table('enrollments')->count()],
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
}
