<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPeakMemory
{
    public const HEADER = 'X-Peak-Memory';

    /**
     * Expose the memory allocated by PHP for this request, in bytes, so that the
     * load generator can collect it without any disk or database write on the server.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(self::HEADER, (string) memory_get_peak_usage());

        return $response;
    }
}
