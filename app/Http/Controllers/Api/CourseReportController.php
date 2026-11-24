<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourseReportRequest;
use App\Http\Resources\CourseReportResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseReportController extends Controller
{
    use QueriesResearchData;

    public function index(CourseReportRequest $request): AnonymousResourceCollection
    {
        return CourseReportResource::collection($this->reportForPeriod($request->integer('period_id')));
    }
}
