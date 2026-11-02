<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseReportRequest;
use App\Http\Resources\CourseReportResource;
use App\Services\Contracts\CourseReportServiceInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseReportController extends Controller
{
    public function __construct(private CourseReportServiceInterface $reports) {}

    public function index(CourseReportRequest $request): AnonymousResourceCollection
    {
        return CourseReportResource::collection($this->reports->forPeriod($request->integer('period_id')));
    }
}
