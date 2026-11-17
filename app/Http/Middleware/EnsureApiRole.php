<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Separates student endpoints from admin endpoints without Spatie roles. A user
 * is a student when a row with the same id exists in the students table, and an
 * admin otherwise (in the research dataset only students and the admin have a
 * token). The check is one query and is the same for every implementation.
 */
class EnsureApiRole
{
    public const STUDENT = 'student';

    public const ADMIN = 'admin';

    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! in_array($role, [self::STUDENT, self::ADMIN], true)) {
            throw new InvalidArgumentException("Unknown API role [{$role}].");
        }

        $isStudent = DB::table('students')->where('id', $request->user()->getAuthIdentifier())->exists();

        abort_unless($isStudent === ($role === self::STUDENT), 403);

        return $next($request);
    }
}
