<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_for_course_returns_every_enrollment_of_the_course_ordered_by_id(): void
    {
        [$course, $otherCourse] = Course::factory()->count(2)->create();
        $paid = $this->enroll(Student::factory()->create(), $course, 2);
        $canceled = $this->enroll(Student::factory()->create(), $course, 3);
        $this->enroll(Student::factory()->create(), $otherCourse, 2);
        $service = new EnrollmentService;

        $enrollments = $service->listForCourse($course->id);

        $this->assertSame([$paid->id, $canceled->id], $enrollments->pluck('id')->all());
    }

    public function test_list_for_course_loads_the_student_and_the_status(): void
    {
        $course = Course::factory()->create();
        $this->enroll(Student::factory()->create(), $course, 2);
        $service = new EnrollmentService;

        $enrollment = $service->listForCourse($course->id)->first();

        $this->assertTrue($enrollment->relationLoaded('student'));
        $this->assertTrue($enrollment->relationLoaded('enrollmentStatus'));
    }

    public function test_list_for_course_fails_for_an_unknown_course(): void
    {
        $service = new EnrollmentService;

        $this->expectException(ModelNotFoundException::class);

        $service->listForCourse(999999);
    }

    private function enroll(Student $student, Course $course, int $statusId): Enrollment
    {
        return Enrollment::withoutEvents(fn () => Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status_id' => $statusId,
        ]));
    }
}
