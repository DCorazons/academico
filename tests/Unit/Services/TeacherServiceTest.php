<?php

namespace Tests\Unit\Services;

use App\Contracts\TeacherRepositoryInterface;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

/** Testability, Repository: the repository is replaced by a mock, without a database. */
class TeacherServiceTest extends TestCase
{
    public function test_paginate_uses_twenty_items_per_page(): void
    {
        $teachers = collect(range(1, 20))->map(fn (int $id) => new Teacher(['id' => $id]));
        $repository = Mockery::mock(TeacherRepositoryInterface::class);
        $repository->shouldReceive('paginate')->once()->with(20)->andReturn(new LengthAwarePaginator($teachers, 21, 20));
        $service = new TeacherService($repository);

        $page = $service->paginate();

        $this->assertSame(20, $page->perPage());
        $this->assertCount(20, $page->items());
    }

    public function test_find_returns_the_requested_teacher(): void
    {
        $teacher = new Teacher(['id' => 7]);
        $repository = Mockery::mock(TeacherRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(7)->andReturn($teacher);
        $service = new TeacherService($repository);

        $found = $service->find($teacher->id);

        $this->assertSame($teacher->id, $found->id);
    }

    public function test_find_fails_for_an_unknown_teacher(): void
    {
        $repository = Mockery::mock(TeacherRepositoryInterface::class);
        $repository->shouldReceive('findOrFail')->once()->with(999999)
            ->andThrow((new ModelNotFoundException)->setModel(Teacher::class, [999999]));
        $service = new TeacherService($repository);

        $this->expectException(ModelNotFoundException::class);

        $service->find(999999);
    }
}
