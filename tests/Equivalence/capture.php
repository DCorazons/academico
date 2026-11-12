<?php

/*
 * Equivalence check (thesis proposal, section 3.2.2): sends every endpoint under test
 * through the HTTP kernel and prints the SQL, the bindings, the status and the body.
 * The outputs of two implementations are compared with diff. Run by capture.sh.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$uris = [
    '/api/teachers',
    '/api/teachers?page=2',
    '/api/teachers/2',
    '/api/teachers/999999',
    '/api/courses',
    '/api/courses?page=3',
    '/api/courses/1',
    '/api/courses/999999',
    '/api/students/35/enrollments',
    '/api/students/999999/enrollments',
    '/api/reports/courses?period_id=1',
    '/api/reports/courses?period_id=999',
];

foreach ($uris as $uri) {
    $request = Request::create($uri, 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer '.config('research.api_token'),
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $kernel->handle($request);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    printf("### GET %s -> %d, %d queries\n", $uri, $response->getStatusCode(), count($queries));
    foreach ($queries as $query) {
        echo 'SQL '.$query['query'].' | '.json_encode($query['bindings'])."\n";
    }
    echo 'BODY '.$response->getContent()."\n";

    $kernel->terminate($request, $response);
}
