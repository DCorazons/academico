<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

/** Testability, Repository: the repository is replaced by a mock, without a database. */
class CourseServiceTest extends TestCase
{
    public function test_paginate_uses_twenty_items_per_page(): void
    {
        $courses = collect(range(1, 20))->map(fn (int $id) => (new Course)->forceFill(['id' => $id]));
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('paginate')->once()->with(20)->andReturn(new LengthAwarePaginator($courses, 21, 20));
        $service = new CourseService($repository);

        $page = $service->paginate();

        $this->assertSame(20, $page->perPage());
        $this->assertCount(20, $page->items());
    }

    public function test_find_returns_the_requested_course(): void
    {
        $course = (new Course)->forceFill(['id' => 7]);
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(7)->andReturn($course);
        $service = new CourseService($repository);

        $found = $service->find($course->id);

        $this->assertSame($course->id, $found->id);
    }

    public function test_find_fails_for_an_unknown_course(): void
    {
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(999999)
            ->andThrow((new ModelNotFoundException)->setModel(Course::class, [999999]));
        $service = new CourseService($repository);

        $this->expectException(ModelNotFoundException::class);

        $service->find(999999);
    }
}
