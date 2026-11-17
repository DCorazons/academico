<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Period;
use App\Models\Teacher;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_returns_20_courses_per_page_ordered_by_id(): void
    {
        $courses = $this->createCourses(21);
        $service = new CourseService;

        $page = $service->paginate();

        $this->assertSame(20, $page->perPage());
        $this->assertSame(21, $page->total());
        $this->assertSame($courses->take(20)->all(), collect($page->items())->pluck('id')->all());
    }

    public function test_paginate_loads_the_period_and_the_teacher(): void
    {
        $this->createCourses(1);
        $service = new CourseService;

        $course = $service->paginate()->items()[0];

        $this->assertTrue($course->relationLoaded('period'));
        $this->assertTrue($course->relationLoaded('teacher'));
    }

    /** @return Collection<int, int> ids of the created courses, ascending */
    private function createCourses(int $count): Collection
    {
        return Course::factory()->count($count)->create([
            'period_id' => Period::factory()->create()->id,
            'teacher_id' => Teacher::factory()->create()->id,
        ])->pluck('id')->sort()->values();
    }
}
