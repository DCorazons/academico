<?php

namespace Tests\Feature\Research;

use App\Http\Requests\ExamSubmissionRequest;
use App\Http\Resources\CourseReportResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\DashboardCourseResource;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamSubmissionResource;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentStatusType;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSubmission;
use App\Models\Material;
use App\Models\Period;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use stdClass;
use Tests\TestCase;

/**
 * The API resources are shared by the three implementations. They must give the
 * same JSON for Eloquent models (active record, repository) and for the plain
 * objects returned by the query builder. No database is used.
 */
class ApiShapeTest extends TestCase
{
    public function test_course_resource_is_the_same_for_models_and_plain_objects(): void
    {
        $model = (new Course)->forceFill([
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'volume' => 48, 'spots' => 50,
            'start_date' => '2026-08-17', 'end_date' => '2027-01-15',
        ]);
        $model->setRelation('period', (new Period)->forceFill(['id' => 1, 'name' => 'Ganjil 2026/2027']));
        $teacher = (new Teacher)->forceFill(['id' => 2]);
        $teacher->setRelation('user', (new User)->forceFill(['firstname' => 'budi', 'lastname' => 'santoso']));
        $model->setRelation('teacher', $teacher);

        $plain = $this->object([
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'volume' => 48, 'spots' => 50,
            'start_date' => '2026-08-17', 'end_date' => '2027-01-15',
            'period' => $this->object(['id' => 1, 'name' => 'Ganjil 2026/2027']),
            'teacher' => $this->object(['id' => 2, 'user' => $this->object(['firstname' => 'budi', 'lastname' => 'santoso'])]),
        ]);

        $expected = [
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'volume' => 48, 'spots' => 50,
            'start_date' => '2026-08-17', 'end_date' => '2027-01-15',
            'period' => ['id' => 1, 'name' => 'Ganjil 2026/2027'],
            'teacher' => ['id' => 2, 'firstname' => 'Budi', 'lastname' => 'SANTOSO'],
        ];

        $this->assertSame($expected, $this->render(new CourseResource($model)));
        $this->assertSame($expected, $this->render(new CourseResource($plain)));
    }

    public function test_enrollment_resource_shows_the_status_name_read_by_the_fallback_translations(): void
    {
        // Same row as the seeded dataset: the fr text only, whatever the application locale
        $status = (new EnrollmentStatusType)->setRawAttributes(['id' => 2, 'name' => json_encode(['fr' => 'PAYÉ'])]);
        $student = (new Student)->forceFill(['id' => 35, 'idnumber' => '2600000001']);
        $student->setRelation('user', (new User)->forceFill(['firstname' => 'siti', 'lastname' => 'aminah']));
        $model = (new Enrollment)->forceFill(['id' => 1]);
        $model->setRelation('student', $student);
        $model->setRelation('enrollmentStatus', $status);

        $plain = $this->object([
            'id' => 1,
            'student' => $this->object(['id' => 35, 'idnumber' => '2600000001', 'user' => $this->object(['firstname' => 'siti', 'lastname' => 'aminah'])]),
            'enrollmentStatus' => $this->object(['id' => 2, 'name' => 'PAYÉ']),
        ]);

        $expected = [
            'id' => 1,
            'student' => ['id' => 35, 'idnumber' => '2600000001', 'firstname' => 'Siti', 'lastname' => 'AMINAH'],
            'status' => ['id' => 2, 'name' => 'PAYÉ'],
        ];

        $this->assertSame($expected, $this->render(new EnrollmentResource($model)));
        $this->assertSame($expected, $this->render(new EnrollmentResource($plain)));
    }

    public function test_dashboard_resource_casts_counts_and_flags(): void
    {
        // MariaDB returns withCount and withExists values as integers
        $model = (new Course)->forceFill(['id' => 1, 'name' => 'Semester 1 Kelas 01', 'materials_count' => 14]);
        $model->setRelation('exams', new Collection([
            (new Exam)->forceFill(['id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01', 'submitted' => 0]),
            (new Exam)->forceFill(['id' => 2, 'type' => 'UAS', 'title' => 'UAS Semester 1 Kelas 01', 'submitted' => 1]),
        ]));

        $plain = $this->object([
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'materials_count' => '14',
            'exams' => [
                $this->object(['id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01', 'submitted' => '0']),
                $this->object(['id' => 2, 'type' => 'UAS', 'title' => 'UAS Semester 1 Kelas 01', 'submitted' => '1']),
            ],
        ]);

        $expected = [
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'materials_count' => 14,
            'exams' => [
                ['id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01', 'submitted' => false],
                ['id' => 2, 'type' => 'UAS', 'title' => 'UAS Semester 1 Kelas 01', 'submitted' => true],
            ],
        ];

        $this->assertSame($expected, $this->render(new DashboardCourseResource($model)));
        $this->assertSame($expected, $this->render(new DashboardCourseResource($plain)));
    }

    public function test_material_resource(): void
    {
        $row = ['id' => 1, 'meeting' => 1, 'title' => 'Pertemuan 1: Pengantar', 'content' => 'Isi materi'];

        $this->assertSame($row, $this->render(new MaterialResource((new Material)->forceFill([...$row, 'course_id' => 1]))));
        $this->assertSame($row, $this->render(new MaterialResource($this->object($row))));
    }

    public function test_exam_resource_never_shows_the_answer_key(): void
    {
        $question = [
            'id' => 1, 'number' => 1, 'question' => 'Apa itu MVC?',
            'option_a' => 'Pola arsitektur', 'option_b' => 'Basis data', 'option_c' => 'Bahasa', 'option_d' => 'Server',
            'correct_option' => 'A',
        ];
        $model = (new Exam)->forceFill(['id' => 1, 'course_id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01']);
        $model->setRelation('questions', new Collection([(new ExamQuestion)->forceFill($question)]));

        $plain = $this->object([
            'id' => 1, 'course_id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01',
            'questions' => [$this->object($question)],
        ]);

        $expected = [
            'id' => 1, 'course_id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01',
            'questions' => [[
                'id' => 1, 'number' => 1, 'question' => 'Apa itu MVC?',
                'options' => ['A' => 'Pola arsitektur', 'B' => 'Basis data', 'C' => 'Bahasa', 'D' => 'Server'],
            ]],
        ];

        $this->assertSame($expected, $this->render(new ExamResource($model)));
        $this->assertSame($expected, $this->render(new ExamResource($plain)));
        $this->assertStringNotContainsString('correct_option', json_encode($this->render(new ExamResource($model))));
    }

    public function test_submission_and_report_resources(): void
    {
        $submission = ['id' => 1, 'exam_id' => 1, 'correct_count' => 20, 'score' => 80];
        $this->assertSame($submission, $this->render(new ExamSubmissionResource((new ExamSubmission)->forceFill([...$submission, 'student_id' => 35]))));
        $this->assertSame($submission, $this->render(new ExamSubmissionResource($this->object($submission))));

        $report = ['id' => 1, 'name' => 'Semester 1 Kelas 01', 'enrollments_count' => 40];
        $this->assertSame($report, $this->render(new CourseReportResource((new Course)->forceFill($report))));
        $this->assertSame($report, $this->render(new CourseReportResource($this->object([...$report, 'enrollments_count' => '40']))));
    }

    public function test_submission_request_accepts_a_complete_answer_list(): void
    {
        $answers = [['question_id' => 1, 'option' => 'A'], ['question_id' => '2', 'option' => 'D']];

        $request = $this->validatedSubmission($answers);

        $this->assertSame([['question_id' => 1, 'option' => 'A'], ['question_id' => 2, 'option' => 'D']], $request->answers());
    }

    public function test_submission_request_rejects_malformed_answers(): void
    {
        $cases = [
            'no answers' => [],
            'not a list' => 'A',
            'unknown option' => [['question_id' => 1, 'option' => 'E']],
            'lower case option' => [['question_id' => 1, 'option' => 'a']],
            'missing option' => [['question_id' => 1]],
            'non integer question' => [['question_id' => 'x', 'option' => 'A']],
            'duplicate question' => [['question_id' => 1, 'option' => 'A'], ['question_id' => 1, 'option' => 'B']],
            'extra key' => [['question_id' => 1, 'option' => 'A', 'correct' => true]],
        ];

        foreach ($cases as $name => $answers) {
            $validator = Validator::make(['answers' => $answers], (new ExamSubmissionRequest)->rules());
            $this->assertTrue($validator->fails(), "Expected [{$name}] to fail validation");
        }
    }

    private function validatedSubmission(array $answers): ExamSubmissionRequest
    {
        $request = ExamSubmissionRequest::create('/api/exams/1/submissions', 'POST', ['answers' => $answers]);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);
        $request->validateResolved();

        return $request;
    }

    private function render(JsonResource $resource): array
    {
        return json_decode($resource->toResponse(Request::create('/'))->getContent(), true)['data'];
    }

    private function object(array $attributes): stdClass
    {
        return (object) $attributes;
    }
}
