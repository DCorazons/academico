<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\ViewData;
use Illuminate\View\View;

class CourseController extends Controller
{
    use QueriesResearchData;

    public function index(): View
    {
        $page = $this->coursePage();

        return view('research.admin.courses', [
            'page' => $page,
            'courses' => ViewData::many(CourseResource::class, $page->items()),
        ]);
    }
}
