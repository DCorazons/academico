<?php

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
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

    public const STUDENTS = 1094;

    public const TEACHERS = 33;

    public const COURSES = 191;

    public const COURSES_PER_STUDENT = 7;

    public const ACTIVE_SEMESTERS = [1, 3, 5, 7];

    public const SEMESTERS = 8;

    public const PAID_STATUS_ID = 2;

    private const ADMIN_ID = 1;

    private const FIRST_TEACHER_ID = 2;

    private const FIRST_STUDENT_ID = self::FIRST_TEACHER_ID + self::TEACHERS;

    private const PERIOD_ID = 1;

    private const SEEDED_AT = '2026-08-01 00:00:00';

    // Same hash as the default admin of DatabaseSeeder ("secret")
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
        $courseIdsBySemester = $this->seedCourses();
        $this->seedEnrollments($courseIdsBySemester);

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

    private function seedUsers(): void
    {
        $faker = FakerFactory::create('id_ID');
        $faker->seed(self::FAKER_SEED);

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
            $users[] = $this->userRow($faker, $id, sprintf('student%04d', $number));
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

    private function userRow($faker, int $id, string $username, ?string $apiToken = null): array
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
     * @return array<int, array<int, int>> course ids, indexed by semester
     */
    private function seedCourses(): array
    {
        $semesters = self::ACTIVE_SEMESTERS;
        $courses = [];
        $courseIdsBySemester = array_fill_keys($semesters, []);

        foreach (range(1, self::COURSES) as $id) {
            $semester = $semesters[($id - 1) % count($semesters)];
            $courseIdsBySemester[$semester][] = $id;

            $courses[] = [
                'id' => $id,
                'campus_id' => 1,
                'rhythm_id' => 1,
                'level_id' => $semester,
                'volume' => 48,
                'name' => sprintf('Semester %d Kelas %02d', $semester, count($courseIdsBySemester[$semester])),
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

        $this->insertInChunks('courses', $courses);

        return $courseIdsBySemester;
    }

    /**
     * Students are split evenly between the active semesters. Inside a semester, each
     * student takes the next consecutive courses, which fills every course evenly.
     *
     * @param  array<int, array<int, int>>  $courseIdsBySemester
     */
    private function seedEnrollments(array $courseIdsBySemester): void
    {
        $semesters = self::ACTIVE_SEMESTERS;
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

    private function insertInChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
