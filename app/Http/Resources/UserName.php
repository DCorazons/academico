<?php

namespace App\Http\Resources;

use Illuminate\Support\Str;

/**
 * The User model formats names in accessors (title case first name, upper case
 * last name), which the query builder does not apply. The resources apply the
 * same formatting again: it is idempotent, so models and plain rows give the
 * same JSON.
 */
final class UserName
{
    public static function first(?string $value): string
    {
        return Str::title((string) $value);
    }

    public static function last(?string $value): string
    {
        return Str::upper((string) $value);
    }
}
