<?php

namespace App\Console\Commands;

use Database\Seeders\ResearchSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Writes the data files of the k6 scripts (thesis proposal, section 3.2.4) from the
 * seeded database, that is from the same state as the dump restored before each run:
 *
 * - meta.json: admin token, period id, and the course ids for the admin endpoints;
 * - students.json: every student with its token and the courses and exams it may open;
 * - write-pairs.json: every student and exam pair that is not submitted yet, in a
 *   shuffled order fixed by a seed, each with the request body of the submission.
 *
 * The files hold tokens and are not committed. The same files are used for the three
 * implementations, so every implementation receives the same requests in the same order.
 */
class ExportK6Data extends Command
{
    public const SEED = 20261117;

    protected $signature = 'research:export-k6-data {--output=storage/app/k6 : Directory of the data files}';

    protected $description = 'Export the student tokens and the unused student and exam pairs for the k6 scripts';

    public function handle(): int
    {
        if (DB::table('exam_submissions')->exists()) {
            $this->error('The database holds exam submissions: restore the seeded dump first, so that every pair is unused.');

            return self::FAILURE;
        }

        $randomizer = new Randomizer(new Mt19937(self::SEED));
        $students = $this->students();
        $pairs = $this->writePairs($students, $randomizer);

        $directory = base_path($this->option('output'));
        File::ensureDirectoryExists($directory);

        $files = [
            'meta.json' => $this->meta(),
            'students.json' => array_values($students),
            'write-pairs.json' => $pairs,
        ];

        foreach ($files as $name => $content) {
            File::put("{$directory}/{$name}", json_encode($content, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        }

        $this->table(['File', 'Rows', 'SHA-256'], array_map(
            fn (string $name) => [
                $name,
                $name === 'meta.json' ? 1 : count($files[$name]),
                hash_file('sha256', "{$directory}/{$name}"),
            ],
            array_keys($files),
        ));
        $this->info("Saved to {$directory}");

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function meta(): array
    {
        return [
            'seed' => self::SEED,
            'admin_token' => DB::table('users')->where('id', ResearchSeeder::ADMIN_ID)->value('api_token'),
            // Session login of the research pages: every seeded user has the same password
            'admin_username' => DB::table('users')->where('id', ResearchSeeder::ADMIN_ID)->value('username'),
            'password' => ResearchSeeder::PASSWORD,
            'period_id' => DB::table('periods')->orderBy('id')->value('id'),
            'course_ids' => DB::table('courses')->orderBy('id')->pluck('id')->all(),
        ];
    }

    /**
     * Students ordered by id, each with the courses it is enrolled in and the exams
     * of those courses, both ordered by id.
     *
     * @return array<int, array{id: int, username: string, token: string, course_ids: array<int, int>, exam_ids: array<int, int>}>
     */
    private function students(): array
    {
        $users = DB::table('users')
            ->whereIn('id', DB::table('students')->select('id'))
            ->orderBy('id')
            ->get(['id', 'username', 'api_token'])
            ->keyBy('id');

        $coursesByStudent = DB::table('enrollments')
            ->orderBy('student_id')
            ->orderBy('course_id')
            ->get(['student_id', 'course_id'])
            ->groupBy('student_id');

        $examsByCourse = DB::table('exams')->orderBy('id')->get(['id', 'course_id'])->groupBy('course_id');

        $students = [];

        foreach ($users as $id => $user) {
            $courseIds = $coursesByStudent->get($id, collect())->pluck('course_id')->map(fn ($courseId) => (int) $courseId)->all();
            $examIds = [];

            foreach ($courseIds as $courseId) {
                foreach ($examsByCourse->get($courseId, collect()) as $exam) {
                    $examIds[] = (int) $exam->id;
                }
            }

            sort($examIds);
            $students[(int) $id] = [
                'id' => (int) $id,
                'username' => $user->username,
                'token' => $user->api_token,
                'course_ids' => $courseIds,
                'exam_ids' => $examIds,
            ];
        }

        return $students;
    }

    /**
     * Every student and exam pair, with a body that answers every question of the
     * exam once, with options drawn from the seed. The pairs are built in student and
     * exam order, then shuffled with the same seed.
     *
     * @param  array<int, array{id: int, token: string, course_ids: array<int, int>, exam_ids: array<int, int>}>  $students
     * @return array<int, array{student_id: int, token: string, exam_id: int, body: string}>
     */
    private function writePairs(array $students, Randomizer $randomizer): array
    {
        $questionsByExam = DB::table('exam_questions')
            ->orderBy('id')
            ->get(['id', 'exam_id'])
            ->groupBy('exam_id')
            ->map(fn ($questions) => $questions->pluck('id')->map(fn ($id) => (int) $id)->all());

        $options = ['A', 'B', 'C', 'D'];
        $pairs = [];

        foreach ($students as $student) {
            foreach ($student['exam_ids'] as $examId) {
                $answers = array_map(fn (int $questionId) => [
                    'question_id' => $questionId,
                    'option' => $options[$randomizer->getInt(0, count($options) - 1)],
                ], $questionsByExam[$examId]);

                $pairs[] = [
                    'student_id' => $student['id'],
                    'token' => $student['token'],
                    'exam_id' => $examId,
                    'body' => json_encode(['answers' => $answers], JSON_THROW_ON_ERROR),
                ];
            }
        }

        return $randomizer->shuffleArray($pairs);
    }
}
