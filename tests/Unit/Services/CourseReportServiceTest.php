<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\Student;
use App\Services\CourseReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_period_returns_the_courses_of_the_period_ordered_by_id(): void
    {
        [$period, $otherPeriod] = Period::factory()->count(2)->create();
        $courses = Course::factory()->count(2)->create(['period_id' => $period->id]);
        Course::factory()->create(['period_id' => $otherPeriod->id]);
        $service = new CourseReportService;

        $report = $service->forPeriod($period->id);

        $this->assertSame($courses->pluck('id')->sort()->values()->all(), $report->pluck('id')->all());
    }

    public function test_for_period_counts_pending_and_paid_enrollments_only(): void
    {
        $period = Period::factory()->create();
        $course = Course::factory()->create(['period_id' => $period->id]);
        foreach ([1, 2, 2, 3] as $statusId) {
            Enrollment::withoutEvents(fn () => Enrollment::query()->create([
                'student_id' => Student::factory()->create()->id,
                'course_id' => $course->id,
                'status_id' => $statusId,
            ]));
        }
        $service = new CourseReportService;

        $report = $service->forPeriod($period->id);

        $this->assertSame(3, (int) $report->first()->enrollments_count);
    }
}
