<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
use App\Services\CourseReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Testability, Active Record: the Service calls Eloquent, so the tests need a database. */
class CourseReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_period_returns_the_enrollment_count_of_each_course(): void
    {
        $period = Period::factory()->create();
        [$first, $second] = Course::factory()->count(2)->create(['period_id' => $period->id])->all();
        Enrollment::withoutEvents(function () use ($first) {
            foreach (Student::factory()->count(2)->create() as $student) {
                Enrollment::create(['student_id' => $student->id, 'course_id' => $first->id, 'status_id' => 2]);
            }
        });
        $service = new CourseReportService;

        $courses = $service->forPeriod($period->id);

        $this->assertSame(
            [$first->id => 2, $second->id => 0],
            $courses->mapWithKeys(fn (Course $course) => [$course->id => (int) $course->enrollments_count])->all(),
        );
    }
}
