<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Resources\CreatedEnrollmentResource;
use App\Http\Resources\EnrollmentResource;
use App\Services\Contracts\EnrollmentServiceInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnrollmentController extends Controller
{
    public function __construct(private EnrollmentServiceInterface $enrollments) {}

    public function index(int $id): AnonymousResourceCollection
    {
        return EnrollmentResource::collection($this->enrollments->listForStudent($id));
    }

    public function store(StoreEnrollmentRequest $request): CreatedEnrollmentResource
    {
        return new CreatedEnrollmentResource($this->enrollments->create(
            $request->integer('student_id'),
            $request->integer('course_id'),
            $request->user()->id,
        ));
    }
}
