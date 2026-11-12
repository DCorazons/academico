<?php

namespace Tests\Unit\Services;

use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Testability, Active Record: the Service calls Eloquent, so the tests need a database. */
class TeacherServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paginate_uses_twenty_items_per_page(): void
    {
        Teacher::factory()->count(21)->create();
        $service = new TeacherService;

        $page = $service->paginate();

        $this->assertSame(20, $page->perPage());
        $this->assertCount(20, $page->items());
    }

    public function test_find_returns_the_requested_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $service = new TeacherService;

        $found = $service->find($teacher->id);

        $this->assertSame($teacher->id, $found->id);
    }

    public function test_find_fails_for_an_unknown_teacher(): void
    {
        $service = new TeacherService;

        $this->expectException(ModelNotFoundException::class);

        $service->find(999999);
    }
}
