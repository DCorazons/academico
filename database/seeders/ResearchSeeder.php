<?php

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds the research dataset described in table 3.3 of the thesis proposal.
 * Rows are written with the query builder so that no model event or activity
 * log entry is produced, and every value is derived from a fixed seed so that
 * two runs give the same data.
 */
class ResearchSeeder extends Seeder
{
    public const FAKER_SEED = 20261019;

    public const TOKEN_SEED = 20261020;

    public const CONTENT_SEED = 20261021;

    public const STUDENTS = 1094;

    public const TEACHERS = 33;

    public const COURSES = 191;

    public const COURSES_PER_STUDENT = 7;

    public const ACTIVE_SEMESTERS = [1, 3, 5, 7];

    public const SEMESTERS = 8;

    public const PAID_STATUS_ID = 2;

    public const MATERIALS_PER_COURSE = 14;

    public const EXAM_TYPES = ['UTS', 'UAS'];

    public const QUESTIONS_PER_EXAM = 25;

    public const OPTIONS = ['A', 'B', 'C', 'D'];

    public const ADMIN_ID = 1;

    public const FIRST_TEACHER_ID = 2;

    public const FIRST_STUDENT_ID = self::FIRST_TEACHER_ID + self::TEACHERS;

    private const PERIOD_ID = 1;

    private const SEEDED_AT = '2026-08-01 00:00:00';

    // Password of every seeded user, for the session login of the research pages
    public const PASSWORD = 'secret';

    // Same hash as the default admin of DatabaseSeeder (PASSWORD)
    private const PASSWORD_HASH = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm';

    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        Carbon::setTestNow(self::SEEDED_AT);
        config(['activitylog.enabled' => false]);

        $this->call([
            ReferenceDataSeeder::class,
            PermissionsSeeder::class,
        ]);

        $this->seedAcademicStructure();
        $this->seedUsers();
        $courses = $this->seedCourses();
        $this->seedEnrollments($courses);

        $content = FakerFactory::create('id_ID');
        $content->seed(self::CONTENT_SEED);
        $this->seedMaterials($content, $courses);
        $this->seedExams($content, $courses);

        Carbon::setTestNow();
    }

    private function seedAcademicStructure(): void
    {
        DB::table('years')->insert(['id' => 1, 'name' => '2026/2027']);

        DB::table('periods')->insert([
            'id' => self::PERIOD_ID,
            'name' => 'Ganjil 2026/2027',
            'start' => '2026-08-17',
            'end' => '2027-01-15',
            'year_id' => 1,
            'order' => 1,
        ]);

        DB::table('rhythms')->insert(['id' => 1, 'name' => 'Reguler']);

        foreach (range(1, self::SEMESTERS) as $semester) {
            DB::table('levels')->insert(['id' => $semester, 'name' => "Semester {$semester}"]);
        }
    }

    /**
     * The admin token comes from config('research.api_token'). Every student gets a
     * token of its own, drawn from a separate seed so that the names stay the same
     * as in the earlier dataset. Teachers get no token.
     */
    private function seedUsers(): void
    {
        $faker = FakerFactory::create('id_ID');
        $faker->seed(self::FAKER_SEED);

        $tokens = FakerFactory::create();
        $tokens->seed(self::TOKEN_SEED);

        $users = [$this->userRow($faker, self::ADMIN_ID, 'admin', config('research.api_token'))];
        $teachers = [];
        $students = [];

        foreach (range(1, self::TEACHERS) as $number) {
            $id = self::FIRST_TEACHER_ID + $number - 1;
            $users[] = $this->userRow($faker, $id, sprintf('teacher%02d', $number));
            $teachers[] = [
                'id' => $id,
                'hired_at' => self::SEEDED_AT,
                'max_week_hours' => 40,
                'created_at' => self::SEEDED_AT,
                'updated_at' => self::SEEDED_AT,
            ];
        }

        foreach (range(1, self::STUDENTS) as $number) {
            $id = self::FIRST_STUDENT_ID + $number - 1;
            $users[] = $this->userRow($faker, $id, sprintf('student%04d', $number), $tokens->unique()->sha256());
            $students[] = [
                'id' => $id,
                'idnumber' => sprintf('26%08d', $number),
                'gender_id' => $faker->numberBetween(1, 2),
                'birthdate' => $faker->date('Y-m-d', '2008-12-31'),
                'created_at' => self::SEEDED_AT,
                'updated_at' => self::SEEDED_AT,
            ];
        }

        $this->insertInChunks('users', $users);
        $this->insertInChunks('teachers', $teachers);
        $this->insertInChunks('students', $students);

        DB::table('model_has_roles')->insert([
            'role_id' => DB::table('roles')->where('name', 'admin')->value('id'),
            'model_type' => 'App\Models\User',
            'model_id' => self::ADMIN_ID,
        ]);
    }

    private function userRow(Generator $faker, int $id, string $username, ?string $apiToken = null): array
    {
        return [
            'id' => $id,
            'username' => $username,
            'firstname' => $faker->firstName(),
            'lastname' => $faker->lastName(),
            'email' => "{$username}@academico.test",
            'password' => self::PASSWORD_HASH,
            'api_token' => $apiToken,
            'locale' => 'en',
            'created_at' => self::SEEDED_AT,
            'updated_at' => self::SEEDED_AT,
        ];
    }

    /**
     * Courses go to the semesters and to the teachers in turn, which gives 47 or 48
     * courses per semester, 6 courses to the first 26 teachers and 5 to the other 7.
     *
     * @return array<int, array{name: string, semester: int}> courses, indexed by id
     */
    private function seedCourses(): array
    {
        $semesters = self::ACTIVE_SEMESTERS;
        $perSemester = array_fill_keys($semesters, 0);
        $rows = [];
        $courses = [];

        foreach (range(1, self::COURSES) as $id) {
            $semester = $semesters[($id - 1) % count($semesters)];
            $perSemester[$semester]++;
            $name = sprintf('Semester %d Kelas %02d', $semester, $perSemester[$semester]);
            $courses[$id] = ['name' => $name, 'semester' => $semester];

            $rows[] = [
                'id' => $id,
                'campus_id' => 1,
                'rhythm_id' => 1,
                'level_id' => $semester,
                'volume' => 48,
                'name' => $name,
                'price' => 0,
                'start_date' => '2026-08-17',
                'end_date' => '2027-01-15',
                'teacher_id' => self::FIRST_TEACHER_ID + ($id - 1) % self::TEACHERS,
                'exempt_attendance' => false,
                'period_id' => self::PERIOD_ID,
                'spots' => 50,
                'evaluation_type_id' => 1,
                'created_at' => self::SEEDED_AT,
                'updated_at' => self::SEEDED_AT,
            ];
        }

        $this->insertInChunks('courses', $rows);

        return $courses;
    }

    /**
     * Students are split evenly between the active semesters. Inside a semester, each
     * student takes the next consecutive courses, which fills every course evenly.
     *
     * @param  array<int, array{name: string, semester: int}>  $courses
     */
    private function seedEnrollments(array $courses): void
    {
        $semesters = self::ACTIVE_SEMESTERS;
        $courseIdsBySemester = array_fill_keys($semesters, []);

        foreach ($courses as $id => $course) {
            $courseIdsBySemester[$course['semester']][] = $id;
        }

        $nextCourse = array_fill_keys($semesters, 0);
        $enrollments = [];

        foreach (range(1, self::STUDENTS) as $number) {
            $semester = $semesters[($number - 1) % count($semesters)];
            $courseIds = $courseIdsBySemester[$semester];

            foreach (range(1, self::COURSES_PER_STUDENT) as $ignored) {
                $enrollments[] = [
                    'student_id' => self::FIRST_STUDENT_ID + $number - 1,
                    'responsible_id' => self::ADMIN_ID,
                    'course_id' => $courseIds[$nextCourse[$semester] % count($courseIds)],
                    'status_id' => self::PAID_STATUS_ID,
                    'created_at' => self::SEEDED_AT,
                    'updated_at' => self::SEEDED_AT,
                ];
                $nextCourse[$semester]++;
            }
        }

        $this->insertInChunks('enrollments', $enrollments);
    }

    /**
     * Fourteen materials per course, one for each meeting outside the two exams.
     *
     * @param  array<int, array{name: string, semester: int}>  $courses
     */
    private function seedMaterials(Generator $faker, array $courses): void
    {
        $materials = [];

        foreach (array_keys($courses) as $courseId) {
            foreach (range(1, self::MATERIALS_PER_COURSE) as $meeting) {
                $materials[] = [
                    'course_id' => $courseId,
                    'meeting' => $meeting,
                    'title' => sprintf('Pertemuan %d: %s', $meeting, rtrim($faker->sentence(4), '.')),
                    'content' => $faker->paragraphs(3, true),
                    'created_at' => self::SEEDED_AT,
                    'updated_at' => self::SEEDED_AT,
                ];
            }
        }

        $this->insertInChunks('materials', $materials);
    }

    /**
     * A midterm (UTS) and a final (UAS) exam per course, each with 25 multiple
     * choice questions. Exam ids follow the course order: course n has the UTS with
     * id 2n - 1 and the UAS with id 2n.
     *
     * @param  array<int, array{name: string, semester: int}>  $courses
     */
    private function seedExams(Generator $faker, array $courses): void
    {
        $exams = [];
        $questions = [];
        $examId = 0;

        foreach ($courses as $courseId => $course) {
            foreach (self::EXAM_TYPES as $type) {
                $examId++;
                $exams[] = [
                    'id' => $examId,
                    'course_id' => $courseId,
                    'type' => $type,
                    'title' => "{$type} {$course['name']}",
                    'created_at' => self::SEEDED_AT,
                    'updated_at' => self::SEEDED_AT,
                ];

                foreach (range(1, self::QUESTIONS_PER_EXAM) as $number) {
                    $questions[] = [
                        'exam_id' => $examId,
                        'number' => $number,
                        'question' => rtrim($faker->sentence(12), '.').'?',
                        'option_a' => rtrim($faker->sentence(4), '.'),
                        'option_b' => rtrim($faker->sentence(4), '.'),
                        'option_c' => rtrim($faker->sentence(4), '.'),
                        'option_d' => rtrim($faker->sentence(4), '.'),
                        'correct_option' => $faker->randomElement(self::OPTIONS),
                        'created_at' => self::SEEDED_AT,
                        'updated_at' => self::SEEDED_AT,
                    ];
                }
            }
        }

        $this->insertInChunks('exams', $exams);
        $this->insertInChunks('exam_questions', $questions);
    }

    private function insertInChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
