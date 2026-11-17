<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Services\Contracts\MaterialServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseMaterialController extends Controller
{
    public function __construct(private MaterialServiceInterface $materials) {}

    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        return MaterialResource::collection($this->materials->listForStudent($id, $request->user()->getAuthIdentifier()));
    }
}
