<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\ViewData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseMaterialController extends Controller
{
    use QueriesResearchData;

    public function index(Request $request, int $id): View
    {
        return view('research.student.materials', [
            'courseId' => $id,
            'materials' => ViewData::many(MaterialResource::class, $this->materialsForStudent($id, $request->user()->getAuthIdentifier())),
        ]);
    }
}
