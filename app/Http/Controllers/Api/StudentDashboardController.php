<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardCourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    public function show(Request $request): AnonymousResourceCollection
    {
        $studentId = $request->user()->getAuthIdentifier();

        $courses = DB::table('courses')
            ->select('courses.*')
            ->selectSub(
                DB::table('materials')->selectRaw('count(*)')->whereColumn('courses.id', 'materials.course_id'),
                'materials_count',
            )
            ->whereIn('id', DB::table('enrollments')->select('course_id')->where('student_id', $studentId))
            ->orderBy('id')
            ->get();

        $exams = DB::table('exams')
            ->select('exams.*')
            ->selectRaw(
                'exists(select * from `exam_submissions` where `exams`.`id` = `exam_submissions`.`exam_id` and `student_id` = ?) as `submitted`',
                [$studentId],
            )
            ->whereIn('exams.course_id', $courses->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('course_id');

        foreach ($courses as $course) {
            $course->exams = $exams->get($course->id, collect())->values();
        }

        return DashboardCourseResource::collection($courses);
    }
}
