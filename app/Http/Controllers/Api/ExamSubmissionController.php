<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamSubmissionRequest;
use App\Http\Resources\ExamSubmissionResource;
use App\Services\Contracts\ExamServiceInterface;
use Illuminate\Http\JsonResponse;

class ExamSubmissionController extends Controller
{
    public function __construct(private ExamServiceInterface $exams) {}

    public function store(ExamSubmissionRequest $request, int $id): JsonResponse
    {
        $submission = $this->exams->submit($id, $request->user()->getAuthIdentifier(), $request->answers());

        return (new ExamSubmissionResource($submission))->response()->setStatusCode(201);
    }
}
