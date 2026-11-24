<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ViewData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    use QueriesResearchData;

    public function show(Request $request, int $id): View
    {
        return view('research.student.exam', [
            'exam' => ViewData::one(new ExamResource($this->examForStudent($id, $request->user()->getAuthIdentifier()))),
        ]);
    }
}
