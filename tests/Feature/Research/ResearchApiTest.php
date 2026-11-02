<?php

namespace Tests\Feature\Research;

use App\Http\Middleware\RecordPeakMemory;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentStatusType;
use App\Models\Period;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract of the endpoints under test. The same file runs on every
 * implementation branch; on `common` the endpoints have no controller yet.
 */
class ResearchApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-token';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['activitylog.enabled' => false]);

        $this->seed(ReferenceDataSeeder::class);

        $this->admin = User::factory()->create(['api_token' => self::TOKEN]);
        $this->withToken(self::TOKEN);
    }

    public function test_endpoints_reject_a_request_without_a_valid_token(): void
    {
        $this->withToken('wrong-token');

        $this->getJson('/api/teachers')->assertUnauthorized();
        $this->getJson('/api/teachers/1')->assertUnauthorized();
        $this->getJson('/api/courses')->assertUnauthorized();
        $this->getJson('/api/courses/1')->assertUnauthorized();
        $this->getJson('/api/students/1/enrollments')->assertUnauthorized();
        $this->postJson('/api/enrollments')->assertUnauthorized();
        $this->getJson('/api/reports/courses')->assertUnauthorized();
    }

    public function test_responses_expose_the_peak_memory(): void
    {
        $this->getJson('/api/teachers')->assertOk()->assertHeader(RecordPeakMemory::HEADER);
    }

    public function test_teachers_are_paginated_by_id(): void
    {
        Teacher::factory()->count(21)->create();

        $response = $this->getJson('/api/teachers')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.per_page', 20);

        $ids = array_column($response->json('data'), 'id');
        $this->assertSame(Teacher::orderBy('id')->limit(20)->pluck('id')->all(), $ids);
    }

    public function test_teacher_detail_has_only_the_agreed_fields(): void
    {
        $teacher = Teacher::factory()->create();
        $user = $teacher->user;

        $this->getJson("/api/teachers/{$teacher->id}")
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $teacher->id,
                'hired_at' => null,
                'max_week_hours' => $teacher->fresh()->max_week_hours,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                ],
            ]]);
    }

    public function test_unknown_teacher_returns_not_found(): void
    {
        $this->getJson('/api/teachers/999999')->assertNotFound();
    }

    public function test_courses_are_paginated_by_id(): void
    {
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();
        Course::factory()->count(21)->create(['teacher_id' => $teacher->id, 'period_id' => $period->id]);

        $response = $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 21)
            ->assertJsonPath('meta.per_page', 20);

        $ids = array_column($response->json('data'), 'id');
        $this->assertSame(Course::orderBy('id')->limit(20)->pluck('id')->all(), $ids);
    }

    public function test_course_detail_has_only_the_agreed_fields(): void
    {
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();
        $course = Course::factory()->create([
            'teacher_id' => $teacher->id,
            'period_id' => $period->id,
            'start_date' => '2026-08-17',
            'end_date' => '2027-01-15',
        ]);

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $course->id,
                'name' => $course->name,
                'volume' => $course->volume,
                'spots' => $course->spots,
                'start_date' => '2026-08-17',
                'end_date' => '2027-01-15',
                'period' => ['id' => $period->id, 'name' => $period->name],
                'teacher' => [
                    'id' => $teacher->id,
                    'firstname' => $teacher->user->firstname,
                    'lastname' => $teacher->user->lastname,
                ],
            ]]);
    }

    public function test_unknown_course_returns_not_found(): void
    {
        $this->getJson('/api/courses/999999')->assertNotFound();
    }

    public function test_student_enrollments_are_ordered_by_id(): void
    {
        $student = Student::factory()->create();
        $courses = $this->coursesInPeriod(2);
        $first = $this->enroll($student, $courses[1]);
        $second = $this->enroll($student, $courses[0]);
        $this->enroll(Student::factory()->create(), $courses[0]);

        $this->getJson("/api/students/{$student->id}/enrollments")
            ->assertOk()
            ->assertExactJson(['data' => [
                $this->enrollmentJson($first, $student, $courses[1]),
                $this->enrollmentJson($second, $student, $courses[0]),
            ]]);
    }

    public function test_unknown_student_returns_not_found(): void
    {
        $this->getJson('/api/students/999999/enrollments')->assertNotFound();
    }

    public function test_enrollment_is_created(): void
    {
        $student = Student::factory()->create();
        $course = $this->coursesInPeriod(1)[0];

        $response = $this->postJson('/api/enrollments', ['student_id' => $student->id, 'course_id' => $course->id])
            ->assertCreated();

        $this->assertDatabaseCount('enrollments', 1);
        $enrollment = Enrollment::first();
        $response->assertExactJson(['data' => [
            'id' => $enrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'responsible_id' => $this->admin->id,
            'status_id' => 1,
        ]]);
    }

    public function test_enrollment_requires_an_existing_student_and_course(): void
    {
        $this->postJson('/api/enrollments', ['student_id' => 999999, 'course_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id', 'course_id']);

        $this->postJson('/api/enrollments')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id', 'course_id']);

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_enrollment_rejects_a_duplicate_pair(): void
    {
        $student = Student::factory()->create();
        $course = $this->coursesInPeriod(1)[0];
        $this->enroll($student, $course);

        $this->postJson('/api/enrollments', ['student_id' => $student->id, 'course_id' => $course->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['course_id']);

        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_course_report_counts_paid_and_pending_enrollments_of_the_period(): void
    {
        [$first, $second] = $this->coursesInPeriod(2);
        $otherPeriodCourse = $this->coursesInPeriod(1)[0];

        $this->enroll(Student::factory()->create(), $first, 2);
        $this->enroll(Student::factory()->create(), $first, 1);
        $this->enroll(Student::factory()->create(), $first, 3);
        $this->enroll(Student::factory()->create(), $otherPeriodCourse, 2);

        $this->getJson("/api/reports/courses?period_id={$first->period_id}")
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => $first->id, 'name' => $first->name, 'enrollments_count' => 2],
                ['id' => $second->id, 'name' => $second->name, 'enrollments_count' => 0],
            ]]);
    }

    public function test_course_report_requires_an_existing_period(): void
    {
        $this->getJson('/api/reports/courses')->assertUnprocessable()->assertJsonValidationErrors(['period_id']);
        $this->getJson('/api/reports/courses?period_id=999999')->assertUnprocessable()->assertJsonValidationErrors(['period_id']);
    }

    /** @return array<int, Course> */
    private function coursesInPeriod(int $count): array
    {
        $teacher = Teacher::factory()->create();
        $period = Period::factory()->create();

        return Course::factory()->count($count)
            ->create(['teacher_id' => $teacher->id, 'period_id' => $period->id])
            ->all();
    }

    private function enroll(Student $student, Course $course, int $statusId = 2): Enrollment
    {
        return Enrollment::withoutEvents(fn () => Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status_id' => $statusId,
        ]));
    }

    private function enrollmentJson(Enrollment $enrollment, Student $student, Course $course): array
    {
        return [
            'id' => $enrollment->id,
            'student' => [
                'id' => $student->id,
                'firstname' => $student->user->firstname,
                'lastname' => $student->user->lastname,
            ],
            'course' => ['id' => $course->id, 'name' => $course->name],
            'status' => ['id' => 2, 'name' => EnrollmentStatusType::find(2)->name],
        ];
    }
}
