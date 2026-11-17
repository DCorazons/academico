<?php

namespace Tests\Unit\Services;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;
use App\Models\Exam;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    private const STUDENT_ID = 35;

    public function test_for_student_returns_the_enrolled_courses_ordered_by_id(): void
    {
        $first = (new Course)->forceFill(['id' => 1]);
        $second = (new Course)->forceFill(['id' => 3]);
        $service = $this->service(new Collection([$first, $second]));

        $courses = $service->forStudent(self::STUDENT_ID);

        $this->assertSame([$first->id, $second->id], $courses->pluck('id')->all());
    }

    public function test_for_student_counts_the_materials_of_each_course(): void
    {
        $course = (new Course)->forceFill(['id' => 1, 'materials_count' => 3]);
        $service = $this->service(new Collection([$course]));

        $courses = $service->forStudent(self::STUDENT_ID);

        $this->assertSame(3, (int) $courses->first()->materials_count);
    }

    public function test_for_student_flags_the_exams_submitted_by_the_student(): void
    {
        $submitted = (new Exam)->forceFill(['id' => 1, 'type' => 'UTS', 'submitted' => 1]);
        $open = (new Exam)->forceFill(['id' => 2, 'type' => 'UAS', 'submitted' => 0]);
        $course = (new Course)->forceFill(['id' => 1])->setRelation('exams', new Collection([$submitted, $open]));
        $service = $this->service(new Collection([$course]));

        $exams = $service->forStudent(self::STUDENT_ID)->first()->exams;

        $this->assertSame([$submitted->id, $open->id], $exams->pluck('id')->all());
        $this->assertSame([true, false], $exams->map(fn ($exam) => (bool) $exam->submitted)->all());
    }

    private function service(Collection $courses): DashboardService
    {
        $repository = Mockery::mock(CourseRepositoryInterface::class);
        $repository->shouldReceive('enrolledByStudentWithMaterialCountAndExams')->once()->with(self::STUDENT_ID)->andReturn($courses);

        return new DashboardService($repository);
    }
}
