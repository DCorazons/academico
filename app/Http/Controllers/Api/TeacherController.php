<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): AnonymousResourceCollection
    {
        $teachers = $this->teachers()
            ->orderBy('teachers.id')
            ->paginate(self::PER_PAGE)
            ->through(fn (object $row) => $this->toResourceData($row));

        return TeacherResource::collection($teachers);
    }

    public function show(int $id): TeacherResource
    {
        $teacher = $this->teachers()->where('teachers.id', $id)->first();

        abort_if($teacher === null, 404);

        return new TeacherResource($this->toResourceData($teacher));
    }

    private function teachers(): Builder
    {
        return DB::table('teachers')
            ->join('users', 'users.id', '=', 'teachers.id')
            ->whereNull('teachers.deleted_at')
            ->select([
                'teachers.id',
                'teachers.hired_at',
                'teachers.max_week_hours',
                'users.username',
                'users.firstname',
                'users.lastname',
                'users.email',
            ]);
    }

    private function toResourceData(object $row): object
    {
        return (object) [
            'id' => $row->id,
            'hired_at' => $row->hired_at,
            'max_week_hours' => $row->max_week_hours,
            'user' => (object) [
                'id' => $row->id,
                'username' => $row->username,
                'firstname' => Str::title($row->firstname),
                'lastname' => Str::upper($row->lastname),
                'email' => $row->email,
            ],
        ];
    }
}
