<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): AnonymousResourceCollection
    {
        $courses = $this->courses()
            ->orderBy('courses.id')
            ->paginate(self::PER_PAGE)
            ->through(fn (object $row) => $this->toResourceData($row));

        return CourseResource::collection($courses);
    }

    public function show(int $id): CourseResource
    {
        $course = $this->courses()->where('courses.id', $id)->first();

        abort_if($course === null, 404);

        return new CourseResource($this->toResourceData($course));
    }

    private function courses(): Builder
    {
        return DB::table('courses')
            ->join('periods', 'periods.id', '=', 'courses.period_id')
            ->join('users', 'users.id', '=', 'courses.teacher_id')
            ->select([
                'courses.id',
                'courses.name',
                'courses.volume',
                'courses.spots',
                'courses.start_date',
                'courses.end_date',
                'courses.period_id',
                'periods.name as period_name',
                'courses.teacher_id',
                'users.firstname',
                'users.lastname',
            ]);
    }

    private function toResourceData(object $row): object
    {
        return (object) [
            'id' => $row->id,
            'name' => $row->name,
            'volume' => $row->volume,
            'spots' => $row->spots,
            'start_date' => $row->start_date,
            'end_date' => $row->end_date,
            'period' => (object) [
                'id' => $row->period_id,
                'name' => $row->period_name,
            ],
            'teacher' => (object) [
                'id' => $row->teacher_id,
                'user' => (object) [
                    'firstname' => Str::title($row->firstname),
                    'lastname' => Str::upper($row->lastname),
                ],
            ],
        ];
    }
}
