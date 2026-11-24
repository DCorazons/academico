<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardCourseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentDashboardController extends Controller
{
    use QueriesResearchData;

    public function show(Request $request): AnonymousResourceCollection
    {
        return DashboardCourseResource::collection($this->dashboardForStudent($request->user()->getAuthIdentifier()));
    }
}
