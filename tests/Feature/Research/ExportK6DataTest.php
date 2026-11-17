<?php

namespace Tests\Feature\Research;

use Database\Seeders\ResearchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ExportK6DataTest extends TestCase
{
    use RefreshDatabase;

    private const OUTPUT = 'storage/framework/testing/k6';

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path(self::OUTPUT));

        parent::tearDown();
    }

    public function test_it_exports_every_student_and_every_unused_pair(): void
    {
        $this->seed(ResearchSeeder::class);

        $this->artisan('research:export-k6-data', ['--output' => self::OUTPUT])->assertSuccessful();

        $meta = $this->read('meta.json');
        $students = $this->read('students.json');
        $pairs = $this->read('write-pairs.json');

        $this->assertSame(config('research.api_token'), $meta['admin_token']);
        $this->assertCount(ResearchSeeder::COURSES, $meta['course_ids']);
        $this->assertCount(ResearchSeeder::STUDENTS, $students);
        $this->assertSame(range(ResearchSeeder::FIRST_STUDENT_ID, ResearchSeeder::FIRST_STUDENT_ID + ResearchSeeder::STUDENTS - 1), array_column($students, 'id'));
        $this->assertCount(ResearchSeeder::COURSES_PER_STUDENT, $students[0]['course_ids']);
        $this->assertCount(ResearchSeeder::COURSES_PER_STUDENT * 2, $students[0]['exam_ids']);

        $expectedPairs = ResearchSeeder::STUDENTS * ResearchSeeder::COURSES_PER_STUDENT * 2;
        $this->assertCount($expectedPairs, $pairs);
        $this->assertCount($expectedPairs, array_unique(array_map(fn ($pair) => $pair['student_id'].'-'.$pair['exam_id'], $pairs)));
    }

    public function test_each_pair_answers_every_question_of_its_exam_once(): void
    {
        $this->seed(ResearchSeeder::class);

        $this->artisan('research:export-k6-data', ['--output' => self::OUTPUT])->assertSuccessful();

        $pair = $this->read('write-pairs.json')[0];
        $answers = json_decode($pair['body'], true)['answers'];

        $this->assertSame(
            DB::table('exam_questions')->where('exam_id', $pair['exam_id'])->orderBy('id')->pluck('id')->all(),
            array_column($answers, 'question_id'),
        );
        $this->assertSame([], array_diff(array_column($answers, 'option'), ['A', 'B', 'C', 'D']));
        $this->assertTrue(DB::table('enrollments')
            ->join('exams', 'exams.course_id', '=', 'enrollments.course_id')
            ->where('enrollments.student_id', $pair['student_id'])
            ->where('exams.id', $pair['exam_id'])
            ->exists());
    }

    public function test_the_output_is_the_same_on_every_run(): void
    {
        $this->seed(ResearchSeeder::class);

        $this->artisan('research:export-k6-data', ['--output' => self::OUTPUT])->assertSuccessful();
        $first = hash_file('sha256', base_path(self::OUTPUT.'/write-pairs.json'));
        $this->artisan('research:export-k6-data', ['--output' => self::OUTPUT])->assertSuccessful();

        $this->assertSame($first, hash_file('sha256', base_path(self::OUTPUT.'/write-pairs.json')));
    }

    public function test_it_refuses_a_database_with_submissions(): void
    {
        $this->seed(ResearchSeeder::class);
        DB::table('exam_submissions')->insert(['exam_id' => 1, 'student_id' => ResearchSeeder::FIRST_STUDENT_ID, 'correct_count' => 0, 'score' => 0]);

        $this->artisan('research:export-k6-data', ['--output' => self::OUTPUT])->assertFailed();
    }

    private function read(string $file): array
    {
        return json_decode(File::get(base_path(self::OUTPUT.'/'.$file)), true);
    }
}
