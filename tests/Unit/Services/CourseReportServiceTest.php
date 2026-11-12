<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Services\CourseReportService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

/** Testability, Repository: the repository is replaced by a mock, without a database. */
class CourseReportServiceTest extends TestCase
{
    public function test_for_period_returns_the_enrollment_count_of_each_course(): void
    {
        $first = (new Course)->forceFill(['id' => 1, 'period_id' => 7, 'enrollments_count' => 2]);
        $second = (new Course)->forceFill(['id' => 2, 'period_id' => 7, 'enrollments_count' => 0]);
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('withEnrollmentCountForPeriod')->once()->with(7)->andReturn(new Collection([$first, $second]));
        $service = new CourseReportService($repository);

        $courses = $service->forPeriod(7);

        $this->assertSame(
            [$first->id => 2, $second->id => 0],
            $courses->mapWithKeys(fn (Course $course) => [$course->id => (int) $course->enrollments_count])->all(),
        );
    }
}
