<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\View\View;

class CourseEnrollmentController extends Controller
{
    public function __construct(private EnrollmentServiceInterface $enrollments) {}

    public function index(int $id): View
    {
        return view('research.admin.enrollments', [
            'courseId' => $id,
            'enrollments' => ViewData::many(EnrollmentResource::class, $this->enrollments->listForCourse($id)),
        ]);
    }
}
