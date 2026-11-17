<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    use QueriesResearchData;

    public function show(Request $request, int $id): ExamResource
    {
        $exam = $this->findExamOrFail($id);
        $this->ensureEnrolled($exam->course_id, $request->user()->getAuthIdentifier());

        $exam->questions = DB::table('exam_questions')->where('exam_id', $id)->orderBy('id')->get();

        return new ExamResource($exam);
    }
}
