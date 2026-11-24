<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardCourseResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\DashboardServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __construct(private DashboardServiceInterface $dashboard) {}

    public function show(Request $request): View
    {
        return view('research.student.dashboard', [
            'courses' => ViewData::many(DashboardCourseResource::class, $this->dashboard->forStudent($request->user()->getAuthIdentifier())),
        ]);
    }
}
