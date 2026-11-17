<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Services\CourseReportService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class CourseReportServiceTest extends TestCase
{
    private const PERIOD_ID = 1;

    public function test_for_period_returns_the_courses_of_the_period_ordered_by_id(): void
    {
        $courses = new Collection([
            (new Course)->forceFill(['id' => 1, 'period_id' => self::PERIOD_ID]),
            (new Course)->forceFill(['id' => 2, 'period_id' => self::PERIOD_ID]),
        ]);
        $service = $this->service($courses);

        $report = $service->forPeriod(self::PERIOD_ID);

        $this->assertSame($courses->pluck('id')->sort()->values()->all(), $report->pluck('id')->all());
    }

    public function test_for_period_counts_pending_and_paid_enrollments_only(): void
    {
        // The repository counts statuses 1 and 2: of the statuses 1, 2, 2, and 3, three
        $course = (new Course)->forceFill(['id' => 1, 'period_id' => self::PERIOD_ID, 'enrollments_count' => 3]);
        $service = $this->service(new Collection([$course]));

        $report = $service->forPeriod(self::PERIOD_ID);

        $this->assertSame(3, (int) $report->first()->enrollments_count);
    }

    private function service(Collection $courses): CourseReportService
    {
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('forPeriodWithEnrollmentCount')->once()->with(self::PERIOD_ID)->andReturn($courses);

        return new CourseReportService($repository);
    }
}
