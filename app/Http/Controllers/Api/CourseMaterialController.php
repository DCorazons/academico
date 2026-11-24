<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseMaterialController extends Controller
{
    use QueriesResearchData;

    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        return MaterialResource::collection($this->materialsForStudent($id, $request->user()->getAuthIdentifier()));
    }
}
