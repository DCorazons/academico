<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseReportRequest;
use App\Http\Resources\CourseReportResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\CourseReportServiceInterface;
use Illuminate\View\View;

class CourseReportController extends Controller
{
    public function __construct(private CourseReportServiceInterface $reports) {}

    public function index(CourseReportRequest $request): View
    {
        $periodId = $request->integer('period_id');

        return view('research.admin.report', [
            'periodId' => $periodId,
            'courses' => ViewData::many(CourseReportResource::class, $this->reports->forPeriod($periodId)),
        ]);
    }
}
