<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Testability, Active Record: the Service calls Eloquent, so the tests need a database. */
class EnrollmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_for_student_returns_the_enrollments_of_the_student(): void
    {
        $student = Student::factory()->create();
        $courses = Course::factory()->count(2)->create();
        $first = $this->enroll($student, $courses[0]);
        $second = $this->enroll($student, $courses[1]);
        $this->enroll(Student::factory()->create(), $courses[0]);
        $service = new EnrollmentService;

        $enrollments = $service->listForStudent($student->id);

        $this->assertSame([$first->id, $second->id], $enrollments->pluck('id')->all());
    }

    public function test_list_for_student_fails_for_an_unknown_student(): void
    {
        $service = new EnrollmentService;

        $this->expectException(ModelNotFoundException::class);

        $service->listForStudent(999999);
    }

    private function enroll(Student $student, Course $course): Enrollment
    {
        return Enrollment::withoutEvents(fn () => Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status_id' => 2,
        ]));
    }
}
