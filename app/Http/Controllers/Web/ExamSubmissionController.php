<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamSubmissionRequest;
use App\Http\Resources\ExamSubmissionResource;
use App\Http\Resources\ViewData;
use Illuminate\Http\RedirectResponse;

class ExamSubmissionController extends Controller
{
    use QueriesResearchData;

    /** Post, redirect, get: back to the dashboard with the score as a flash message */
    public function store(ExamSubmissionRequest $request, int $id): RedirectResponse
    {
        $submission = ViewData::one(new ExamSubmissionResource(
            $this->submitExam($id, $request->user()->getAuthIdentifier(), $request->answers())
        ));

        return redirect()->route('research.dashboard', status: 303)
            ->with('status', "Jawaban ujian #{$submission['exam_id']} tersimpan: {$submission['correct_count']} benar, nilai {$submission['score']}.");
    }
}
