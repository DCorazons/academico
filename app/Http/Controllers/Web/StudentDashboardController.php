<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardCourseResource;
use App\Http\Resources\ViewData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    use QueriesResearchData;

    public function show(Request $request): View
    {
        return view('research.student.dashboard', [
            'courses' => ViewData::many(DashboardCourseResource::class, $this->dashboardForStudent($request->user()->getAuthIdentifier())),
        ]);
    }
}
