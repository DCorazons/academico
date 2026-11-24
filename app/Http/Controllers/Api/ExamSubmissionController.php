<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamSubmissionRequest;
use App\Http\Resources\ExamSubmissionResource;
use Illuminate\Http\JsonResponse;

class ExamSubmissionController extends Controller
{
    use QueriesResearchData;

    public function store(ExamSubmissionRequest $request, int $id): JsonResponse
    {
        $submission = $this->submitExam($id, $request->user()->getAuthIdentifier(), $request->answers());

        return (new ExamSubmissionResource($submission))->response()->setStatusCode(201);
    }
}
