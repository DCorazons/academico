<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\ExamServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private ExamServiceInterface $exams) {}

    public function show(Request $request, int $id): View
    {
        return view('research.student.exam', [
            'exam' => ViewData::one(new ExamResource($this->exams->showForStudent($id, $request->user()->getAuthIdentifier()))),
        ]);
    }
}
