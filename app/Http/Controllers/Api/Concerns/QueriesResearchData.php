<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Helpers of the query builder controllers. The errors are the same exceptions
 * as in the other implementations, so that the status codes and bodies match.
 */
trait QueriesResearchData
{
    private function ensureCourseExists(int $courseId): void
    {
        if (! DB::table('courses')->where('id', $courseId)->exists()) {
            throw (new ModelNotFoundException)->setModel('App\Models\Course', [$courseId]);
        }
    }

    private function ensureEnrolled(int $courseId, int $studentId): void
    {
        $isEnrolled = DB::table('enrollments')
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->exists();

        if (! $isEnrolled) {
            throw new AuthorizationException;
        }
    }

    private function findExamOrFail(int $examId): object
    {
        $exam = DB::table('exams')->where('id', $examId)->first();

        if ($exam === null) {
            throw (new ModelNotFoundException)->setModel('App\Models\Exam', [$examId]);
        }

        return $exam;
    }

    /**
     * Rows of a table indexed by id, for the given ids.
     *
     * @return Collection<int, object>
     */
    private function keyedById(string $table, Collection $ids): Collection
    {
        return DB::table($table)->whereIn('id', $ids->unique()->values())->get()->keyBy('id');
    }
}
