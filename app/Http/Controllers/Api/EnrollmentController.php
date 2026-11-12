<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
