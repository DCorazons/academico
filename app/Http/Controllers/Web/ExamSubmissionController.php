<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamSubmissionRequest;
use App\Http\Resources\ExamSubmissionResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\ExamServiceInterface;
use Illuminate\Http\RedirectResponse;

class ExamSubmissionController extends Controller
{
    public function __construct(private ExamServiceInterface $exams) {}

    /** Post, redirect, get: back to the dashboard with the score as a flash message */
    public function store(ExamSubmissionRequest $request, int $id): RedirectResponse
    {
        $submission = ViewData::one(new ExamSubmissionResource(
            $this->exams->submit($id, $request->user()->getAuthIdentifier(), $request->answers())
        ));

        return redirect()->route('research.dashboard', status: 303)
            ->with('status', "Jawaban ujian #{$submission['exam_id']} tersimpan: {$submission['correct_count']} benar, nilai {$submission['score']}.");
    }
}
