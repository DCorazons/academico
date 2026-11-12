<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    public function index(int $id): AnonymousResourceCollection
    {
        abort_unless(DB::table('students')->where('id', $id)->exists(), 404);

        $enrollments = DB::table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.student_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->join('enrollment_status_types', 'enrollment_status_types.id', '=', 'enrollments.status_id')
            ->where('enrollments.student_id', $id)
            ->orderBy('enrollments.id')
            ->select([
                'enrollments.id',
                'enrollments.student_id',
                'users.firstname',
                'users.lastname',
                'enrollments.course_id',
                'courses.name as course_name',
                'enrollments.status_id',
                'enrollment_status_types.name as status_name',
            ])
            ->get()
            ->map(fn (object $row) => $this->toResourceData($row));

        return EnrollmentResource::collection($enrollments);
    }

    private function toResourceData(object $row): object
    {
        return (object) [
            'id' => $row->id,
            'student' => (object) [
                'id' => $row->student_id,
                'user' => (object) [
                    'firstname' => Str::title($row->firstname),
                    'lastname' => Str::upper($row->lastname),
                ],
            ],
            'course' => (object) [
                'id' => $row->course_id,
                'name' => $row->course_name,
            ],
            'enrollmentStatus' => (object) [
                'id' => $row->status_id,
                'name' => $this->translatedName($row->status_name),
            ],
        ];
    }

    /** The application reads translated columns in the order fr, es, en */
    private function translatedName(string $json): string
    {
        $translations = json_decode($json, true);

        return $translations['fr'] ?? $translations['es'] ?? $translations['en'] ?? '';
    }
}
