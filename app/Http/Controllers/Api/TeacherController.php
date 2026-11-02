<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Services\Contracts\TeacherServiceInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeacherController extends Controller
{
    public function __construct(private TeacherServiceInterface $teachers) {}

    public function index(): AnonymousResourceCollection
    {
        return TeacherResource::collection($this->teachers->paginate());
    }

    public function show(int $id): TeacherResource
    {
        return new TeacherResource($this->teachers->find($id));
    }
}
