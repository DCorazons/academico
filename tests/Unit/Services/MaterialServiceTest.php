<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\MaterialRepositoryInterface;
use App\Models\Material;
use App\Services\MaterialService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;

class MaterialServiceTest extends TestCase
{
    private const STUDENT_ID = 35;

    public function test_list_for_student_returns_the_materials_of_the_course_ordered_by_id(): void
    {
        $materials = new Collection([
            (new Material)->forceFill(['id' => 1, 'course_id' => 5]),
            (new Material)->forceFill(['id' => 2, 'course_id' => 5]),
        ]);
        $service = $this->service(5, courseExists: true, enrolled: true, materials: $materials);

        $result = $service->listForStudent(5, self::STUDENT_ID);

        $this->assertSame($materials->pluck('id')->sort()->values()->all(), $result->pluck('id')->all());
    }

    public function test_list_for_student_fails_for_an_unknown_course(): void
    {
        $service = $this->service(999999, courseExists: false, enrolled: null, materials: null);

        $this->expectException(ModelNotFoundException::class);

        $service->listForStudent(999999, self::STUDENT_ID);
    }

    public function test_list_for_student_denies_a_student_not_enrolled_in_the_course(): void
    {
        $service = $this->service(5, courseExists: true, enrolled: false, materials: null);

        $this->expectException(AuthorizationException::class);

        $service->listForStudent(5, self::STUDENT_ID);
    }

    private function service(int $courseId, bool $courseExists, ?bool $enrolled, ?Collection $materials): MaterialService
    {
        $courses = Mockery::mock(CourseRepositoryInterface::class);
        $courses->shouldReceive('exists')->once()->with($courseId)->andReturn($courseExists);
        $enrollments = Mockery::mock(EnrollmentRepositoryInterface::class);
        $materialRepository = Mockery::mock(MaterialRepositoryInterface::class);

        if ($enrolled === null) {
            $enrollments->shouldNotReceive('isEnrolled');
        } else {
            $enrollments->shouldReceive('isEnrolled')->once()->with($courseId, self::STUDENT_ID)->andReturn($enrolled);
        }

        if ($materials === null) {
            $materialRepository->shouldNotReceive('forCourse');
        } else {
            $materialRepository->shouldReceive('forCourse')->once()->with($courseId)->andReturn($materials);
        }

        return new MaterialService($courses, $enrollments, $materialRepository);
    }
}
