<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseReportRequest;
use App\Http\Resources\CourseReportResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CourseReportController extends Controller
{
    /** Same statuses as the enrollments relation of App\Models\Course (pending, paid) */
    private const COUNTED_STATUSES = ['1', '2'];

    public function index(CourseReportRequest $request): AnonymousResourceCollection
    {
        $courses = DB::table('courses')
            ->select('courses.*')
            ->selectSub(
                DB::table('enrollments')
                    ->selectRaw('count(*)')
                    ->whereColumn('courses.id', 'enrollments.course_id')
                    ->whereIn('status_id', self::COUNTED_STATUSES),
                'enrollments_count',
            )
            ->where('period_id', $request->integer('period_id'))
            ->orderBy('id')
            ->get();

        return CourseReportResource::collection($courses);
    }
}
