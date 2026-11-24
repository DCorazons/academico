<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\ViewData;
use Illuminate\View\View;

class CourseEnrollmentController extends Controller
{
    use QueriesResearchData;

    public function index(int $id): View
    {
        return view('research.admin.enrollments', [
            'courseId' => $id,
            'enrollments' => ViewData::many(EnrollmentResource::class, $this->enrollmentsForCourse($id)),
        ]);
    }
}
