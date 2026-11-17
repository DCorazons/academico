<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\QueriesResearchData;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CourseEnrollmentController extends Controller
{
    use QueriesResearchData;

    /** Locale order of App\Models\Concerns\HasFallbackTranslations */
    private const LOCALE_PRIORITY = ['fr', 'es', 'en'];

    public function index(int $id): AnonymousResourceCollection
    {
        $this->ensureCourseExists($id);

        $enrollments = DB::table('enrollments')->where('course_id', $id)->orderBy('id')->get();

        $students = $this->keyedById('students', $enrollments->pluck('student_id'));
        // Student::user excludes soft deleted users (Teacher::user does not)
        $users = DB::table('users')->whereIn('id', $students->keys())->whereNull('deleted_at')->get()->keyBy('id');
        $statuses = $this->keyedById('enrollment_status_types', $enrollments->pluck('status_id'));

        foreach ($enrollments as $enrollment) {
            $student = clone $students[$enrollment->student_id];
            $student->user = $users[$enrollment->student_id];
            $status = clone $statuses[$enrollment->status_id];
            $status->name = $this->translated($status->name);

            $enrollment->student = $student;
            $enrollment->enrollmentStatus = $status;
        }

        return EnrollmentResource::collection($enrollments);
    }

    /** The first non-empty text in locale order, then any non-empty text */
    private function translated(?string $json): string
    {
        $translations = json_decode((string) $json, true) ?: [];

        foreach (self::LOCALE_PRIORITY as $locale) {
            if (isset($translations[$locale]) && $translations[$locale] !== '') {
                return $translations[$locale];
            }
        }

        foreach ($translations as $value) {
            if ($value !== '' && $value !== null) {
                return $value;
            }
        }

        return '';
    }
}
