<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    use QueriesResearchData;

    private const PER_PAGE = 20;

    public function index(): AnonymousResourceCollection
    {
        $page = DB::table('courses')->orderBy('id')->paginate(self::PER_PAGE);
        $courses = collect($page->items());

        $periods = $this->keyedById('periods', $courses->pluck('period_id'));
        $teachers = $this->keyedById('teachers', $courses->pluck('teacher_id'));
        $users = $this->keyedById('users', $teachers->keys());

        foreach ($courses as $course) {
            $course->period = $periods[$course->period_id];
            $course->teacher = $teachers[$course->teacher_id];
            $course->teacher->user = $users[$course->teacher_id];
        }

        return CourseResource::collection($page);
    }
}
