<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\MaterialServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseMaterialController extends Controller
{
    public function __construct(private MaterialServiceInterface $materials) {}

    public function index(Request $request, int $id): View
    {
        return view('research.student.materials', [
            'courseId' => $id,
            'materials' => ViewData::many(MaterialResource::class, $this->materials->listForStudent($id, $request->user()->getAuthIdentifier())),
        ]);
    }
}
