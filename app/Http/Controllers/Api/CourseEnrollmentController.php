<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseEnrollmentController extends Controller
{
    use QueriesResearchData;

    public function index(int $id): AnonymousResourceCollection
    {
        return EnrollmentResource::collection($this->enrollmentsForCourse($id));
    }
}
