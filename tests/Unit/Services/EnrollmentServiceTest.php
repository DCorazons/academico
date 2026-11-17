<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Contracts\EnrollmentRepositoryInterface;
use App\Models\Enrollment;
use App\Models\EnrollmentStatusType;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;

class EnrollmentServiceTest extends TestCase
{
    public function test_list_for_course_returns_every_enrollment_of_the_course_ordered_by_id(): void
    {
        $paid = (new Enrollment)->forceFill(['id' => 1, 'course_id' => 5, 'status_id' => 2]);
        $canceled = (new Enrollment)->forceFill(['id' => 2, 'course_id' => 5, 'status_id' => 3]);
        $service = $this->service(5, true, new Collection([$paid, $canceled]));

        $enrollments = $service->listForCourse(5);

        $this->assertSame([$paid->id, $canceled->id], $enrollments->pluck('id')->all());
    }

    public function test_list_for_course_loads_the_student_and_the_status(): void
    {
        $enrollment = (new Enrollment)->forceFill(['id' => 1, 'course_id' => 5])
            ->setRelation('student', new Student)
            ->setRelation('enrollmentStatus', new EnrollmentStatusType);
        $service = $this->service(5, true, new Collection([$enrollment]));

        $enrollment = $service->listForCourse(5)->first();

        $this->assertTrue($enrollment->relationLoaded('student'));
        $this->assertTrue($enrollment->relationLoaded('enrollmentStatus'));
    }

    public function test_list_for_course_fails_for_an_unknown_course(): void
    {
        $service = $this->service(999999, false, null);

        $this->expectException(ModelNotFoundException::class);

        $service->listForCourse(999999);
    }

    private function service(int $courseId, bool $courseExists, ?Collection $enrollments): EnrollmentService
    {
        $courseRepository = Mockery::mock(CourseRepositoryInterface::class);
        $courseRepository->shouldReceive('exists')->once()->with($courseId)->andReturn($courseExists);
        $enrollmentRepository = Mockery::mock(EnrollmentRepositoryInterface::class);

        if ($enrollments === null) {
            $enrollmentRepository->shouldNotReceive('forCourseWithStudentAndStatus');
        } else {
            $enrollmentRepository->shouldReceive('forCourseWithStudentAndStatus')->once()->with($courseId)->andReturn($enrollments);
        }

        return new EnrollmentService($courseRepository, $enrollmentRepository);
    }
}
