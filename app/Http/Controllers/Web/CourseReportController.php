<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseReportRequest;
use App\Http\Resources\CourseReportResource;
use App\Http\Resources\ViewData;
use Illuminate\View\View;

class CourseReportController extends Controller
{
    use QueriesResearchData;

    public function index(CourseReportRequest $request): View
    {
        $periodId = $request->integer('period_id');

        return view('research.admin.report', [
            'periodId' => $periodId,
            'courses' => ViewData::many(CourseReportResource::class, $this->reportForPeriod($periodId)),
        ]);
    }
}
