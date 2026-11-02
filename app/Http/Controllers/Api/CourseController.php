<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Services\Contracts\CourseServiceInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function __construct(private CourseServiceInterface $courses) {}

    public function index(): AnonymousResourceCollection
    {
        return CourseResource::collection($this->courses->paginate());
    }

    public function show(int $id): CourseResource
    {
        return new CourseResource($this->courses->find($id));
    }
}
