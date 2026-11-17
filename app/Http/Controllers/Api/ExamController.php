<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Services\Contracts\ExamServiceInterface;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function __construct(private ExamServiceInterface $exams) {}

    public function show(Request $request, int $id): ExamResource
    {
        return new ExamResource($this->exams->showForStudent($id, $request->user()->getAuthIdentifier()));
    }
}
