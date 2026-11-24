<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\ViewData;
use App\Services\Contracts\CourseServiceInterface;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private CourseServiceInterface $courses) {}

    public function index(): View
    {
        $page = $this->courses->paginate();

        return view('research.admin.courses', [
            'page' => $page,
            'courses' => ViewData::many(CourseResource::class, $page->items()),
        ]);
    }
}
