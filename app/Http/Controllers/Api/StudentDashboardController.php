<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardCourseResource;
use App\Services\Contracts\DashboardServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentDashboardController extends Controller
{
    public function __construct(private DashboardServiceInterface $dashboard) {}

    public function show(Request $request): AnonymousResourceCollection
    {
        return DashboardCourseResource::collection($this->dashboard->forStudent($request->user()->getAuthIdentifier()));
    }
}
