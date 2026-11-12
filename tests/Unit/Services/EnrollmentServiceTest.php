<?php

namespace Tests\Unit\Services;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\StudentRepositoryInterface;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Tests\TestCase;

/** Testability, Repository: the repositories are replaced by mocks, without a database. */
class EnrollmentServiceTest extends TestCase
{
    public function test_list_for_student_returns_the_enrollments_of_the_student(): void
    {
        $student = new Student(['id' => 7]);
        $first = (new Enrollment)->forceFill(['id' => 1, 'student_id' => 7]);
        $second = (new Enrollment)->forceFill(['id' => 2, 'student_id' => 7]);
        $students = Mockery::mock(StudentRepositoryInterface::class);
        $students->shouldReceive('findOrFail')->once()->with(7)->andReturn($student);
        $enrollments = Mockery::mock(EnrollmentRepositoryInterface::class);
        $enrollments->shouldReceive('forStudent')->once()->with(7)->andReturn(new Collection([$first, $second]));
        $service = new EnrollmentService($enrollments, $students);

        $result = $service->listForStudent($student->id);

        $this->assertSame([$first->id, $second->id], $result->pluck('id')->all());
    }

    public function test_list_for_student_fails_for_an_unknown_student(): void
    {
        $students = Mockery::mock(StudentRepositoryInterface::class);
        $students->shouldReceive('findOrFail')->once()->with(999999)
            ->andThrow((new ModelNotFoundException)->setModel(Student::class, [999999]));
        $enrollments = Mockery::mock(EnrollmentRepositoryInterface::class);
        $service = new EnrollmentService($enrollments, $students);

        $this->expectException(ModelNotFoundException::class);

        $service->listForStudent(999999);
    }
}
