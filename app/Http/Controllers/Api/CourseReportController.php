<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseReportRequest;
use App\Http\Resources\CourseReportResource;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CourseReportController extends Controller
{
    private const COUNTED_STATUS_IDS = [1, 2];

    public function index(CourseReportRequest $request): AnonymousResourceCollection
    {
        $courses = DB::table('courses')
            ->leftJoin('enrollments', function (JoinClause $join) {
                $join->on('enrollments.course_id', '=', 'courses.id')
                    ->whereIn('enrollments.status_id', self::COUNTED_STATUS_IDS);
            })
            ->where('courses.period_id', $request->integer('period_id'))
            ->groupBy('courses.id', 'courses.name')
            ->orderBy('courses.id')
            ->select('courses.id', 'courses.name')
            ->selectRaw('COUNT(enrollments.id) as enrollments_count')
            ->get();

        return CourseReportResource::collection($courses);
    }
}
