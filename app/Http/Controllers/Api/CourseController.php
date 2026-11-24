<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    use QueriesResearchData;

    public function index(): AnonymousResourceCollection
    {
        return CourseResource::collection($this->coursePage());
    }
}
