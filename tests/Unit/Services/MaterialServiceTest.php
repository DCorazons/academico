<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Material;
use App\Models\Student;
use App\Services\MaterialService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_for_student_returns_the_materials_of_the_course_ordered_by_id(): void
    {
        $student = Student::factory()->create();
        [$course, $otherCourse] = Course::factory()->count(2)->create();
        $this->enroll($student, $course);
        $materials = Material::factory()->count(2)->create(['course_id' => $course->id]);
        Material::factory()->create(['course_id' => $otherCourse->id]);
        $service = new MaterialService;

        $result = $service->listForStudent($course->id, $student->id);

        $this->assertSame($materials->pluck('id')->sort()->values()->all(), $result->pluck('id')->all());
    }

    public function test_list_for_student_fails_for_an_unknown_course(): void
    {
        $student = Student::factory()->create();
        $service = new MaterialService;

        $this->expectException(ModelNotFoundException::class);

        $service->listForStudent(999999, $student->id);
    }

    public function test_list_for_student_denies_a_student_not_enrolled_in_the_course(): void
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $service = new MaterialService;

        $this->expectException(AuthorizationException::class);

        $service->listForStudent($course->id, $student->id);
    }

    private function enroll(Student $student, Course $course): void
    {
        Enrollment::withoutEvents(fn () => Enrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status_id' => 2,
        ]));
    }
}
