<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Period;
use App\Models\Teacher;
use App\Services\CourseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Testability, Active Record: the Service calls Eloquent, so the tests need a database. */
class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_uses_twenty_items_per_page(): void
    {
        Course::factory()->count(21)->create([
            'teacher_id' => Teacher::factory()->create()->id,
            'period_id' => Period::factory()->create()->id,
        ]);
        $service = new CourseService;

        $page = $service->paginate();

        $this->assertSame(20, $page->perPage());
        $this->assertCount(20, $page->items());
    }

    public function test_find_returns_the_requested_course(): void
    {
        $course = Course::factory()->create();
        $service = new CourseService;

        $found = $service->find($course->id);

        $this->assertSame($course->id, $found->id);
    }

    public function test_find_fails_for_an_unknown_course(): void
    {
        $service = new CourseService;

        $this->expectException(ModelNotFoundException::class);

        $service->find(999999);
    }
}
