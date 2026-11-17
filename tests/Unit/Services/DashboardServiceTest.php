<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\Material;
use App\Models\Student;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_student_returns_the_enrolled_courses_ordered_by_id(): void
    {
        $student = Student::factory()->create();
        [$first, $other, $second] = Course::factory()->count(3)->create();
        $this->enroll($student, $second);
        $this->enroll($student, $first);
        $this->enroll(Student::factory()->create(), $other);
        $service = new DashboardService;

        $courses = $service->forStudent($student->id);

        $this->assertSame([$first->id, $second->id], $courses->pluck('id')->all());
    }

    public function test_for_student_counts_the_materials_of_each_course(): void
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $this->enroll($student, $course);
        Material::factory()->count(3)->create(['course_id' => $course->id]);
        $service = new DashboardService;

        $courses = $service->forStudent($student->id);

        $this->assertSame(3, (int) $courses->first()->materials_count);
    }

    public function test_for_student_flags_the_exams_submitted_by_the_student(): void
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $this->enroll($student, $course);
        $submitted = Exam::factory()->create(['course_id' => $course->id, 'type' => 'UTS']);
        $open = Exam::factory()->create(['course_id' => $course->id, 'type' => 'UAS']);
        ExamSubmission::factory()->create(['exam_id' => $submitted->id, 'student_id' => $student->id]);
        ExamSubmission::factory()->create(['exam_id' => $open->id]);
        $service = new DashboardService;

        $exams = $service->forStudent($student->id)->first()->exams;

        $this->assertSame([$submitted->id, $open->id], $exams->pluck('id')->all());
        $this->assertSame([true, false], $exams->map(fn ($exam) => (bool) $exam->submitted)->all());
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
