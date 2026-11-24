<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    use QueriesResearchData;

    public function show(Request $request, int $id): ExamResource
    {
        return new ExamResource($this->examForStudent($id, $request->user()->getAuthIdentifier()));
    }
}
