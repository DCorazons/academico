<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Models\Period;
use App\Models\Teacher;
use App\Services\CourseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    public function test_paginate_returns_20_courses_per_page_ordered_by_id(): void
    {
        $courses = collect(range(1, 21));
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('paginateWithPeriodAndTeacher')->once()->with(20)
            ->andReturn(new LengthAwarePaginator($this->courses($courses->take(20)), 21, 20, 1));
        $service = new CourseService($repository);

        $page = $service->paginate();

        $this->assertSame(20, $page->perPage());
        $this->assertSame(21, $page->total());
        $this->assertSame($courses->take(20)->all(), collect($page->items())->pluck('id')->all());
    }

    public function test_paginate_loads_the_period_and_the_teacher(): void
    {
        $course = $this->courses(collect([1]))->first()
            ->setRelation('period', new Period)
            ->setRelation('teacher', new Teacher);
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('paginateWithPeriodAndTeacher')->once()->with(20)
            ->andReturn(new LengthAwarePaginator([$course], 1, 20, 1));
        $service = new CourseService($repository);

        $course = $service->paginate()->items()[0];

        $this->assertTrue($course->relationLoaded('period'));
        $this->assertTrue($course->relationLoaded('teacher'));
    }

    /** @param  Collection<int, int>  $ids */
    private function courses(Collection $ids): Collection
    {
        return $ids->map(fn (int $id) => (new Course)->forceFill(['id' => $id]));
    }
}
