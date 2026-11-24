<?php

/*
 * Equivalence check of the server-rendered research pages: logs in like a browser
 * (session cookie, CSRF token), sends a fixed list of page requests through the HTTP
 * kernel, and prints the status, redirect target, SQL with bindings, and HTML. Values
 * that are random on every request (CSRF tokens, session ids and payloads, Livewire
 * component ids and snapshots, rehashed passwords) are replaced by placeholders so that
 * two implementations can be compared with compare.sh. Run by capture.sh.
 */

use Database\Seeders\ResearchSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

Carbon::setTestNow('2026-11-17 08:00:00');

$studentId = 35;
$adminName = DB::table('users')->where('id', ResearchSeeder::ADMIN_ID)->value('username');
$studentName = DB::table('users')->where('id', $studentId)->value('username');
$enrolledCourse = DB::table('enrollments')->where('student_id', $studentId)->orderBy('course_id')->value('course_id');
$otherCourse = DB::table('courses')
    ->whereNotIn('id', DB::table('enrollments')->where('student_id', $studentId)->select('course_id'))
    ->orderBy('id')
    ->value('id');
$exam = DB::table('exams')->where('course_id', $enrolledCourse)->orderBy('id')->value('id');
$otherExam = DB::table('exams')->where('course_id', $otherCourse)->orderBy('id')->value('id');

// 20 correct answers and 5 wrong ones, as a form would send them
$answers = [];
foreach (DB::table('exam_questions')->where('exam_id', $exam)->orderBy('id')->pluck('correct_option', 'id') as $id => $key) {
    $answers[] = ['question_id' => $id, 'option' => count($answers) < 20 ? $key : ($key === 'A' ? 'B' : 'A')];
}

$cookies = [];
$csrf = null;
$previous = null;

$normalize = function (string $html): string {
    $html = preg_replace('/(name="_token" value=")[^"]+/', '$1<csrf>', $html);
    $html = preg_replace('/(name="csrf-token" content=")[^"]+/', '$1<csrf>', $html);
    $html = preg_replace('/wire:(id|snapshot|effects)="[^"]*"/', 'wire:$1="<livewire>"', $html);
    $html = preg_replace('/data-update-uri="[^"]*"|data-csrf="[^"]*"/', '<livewire-attr>', $html);

    return str_replace(["\r", "\n"], '', $html);
};

$send = function (string $label, string $method, string $uri, array $form = []) use ($app, $kernel, &$cookies, &$csrf, &$previous, $normalize) {
    // Each request starts like a new process: no user, session, or redirector (which
    // keeps the session store it was built with, for flash data and errors) in memory
    $app['auth']->forgetGuards();
    $app['session']->forgetDrivers();
    $app->forgetInstance('session.store');
    $app->forgetInstance('redirect');

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries) {
        $bindings = $query->bindings;
        // Session rows and rehashed passwords hold random values
        if (str_contains($query->sql, '`sessions`') || str_contains($query->sql, 'set `password`')) {
            $bindings = array_map(fn ($value) => is_string($value) && strlen($value) > 20 ? '<random>' : $value, $bindings);
        }
        $queries[] = $query->sql.' | '.json_encode($bindings);
    });

    if ($method === 'POST') {
        $form['_token'] = $csrf;
    }
    $server = ['HTTP_ACCEPT' => 'text/html'] + ($previous ? ['HTTP_REFERER' => $previous] : []);
    $request = Request::create($uri, $method, $form, $cookies, [], $server);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $app['events']->forget(QueryExecuted::class);

    foreach ($response->headers->getCookies() as $cookie) {
        $cookies[$cookie->getName()] = $cookie->getValue();
    }
    $html = (string) $response->getContent();
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $match)) {
        $csrf = $match[1];
    }
    if ($method === 'GET' && $response->getStatusCode() === 200) {
        $previous = 'http://localhost'.$uri;
    }

    $location = $response->headers->get('Location');
    printf("### %s %s %s -> %d%s, %d queries\n", $label, $method, $uri, $response->getStatusCode(), $location ? " {$location}" : '', count($queries));
    foreach ($queries as $query) {
        echo "SQL {$query}\n";
    }
    echo 'BODY '.$normalize($html)."\n";
};

$credentials = fn (string $username) => ['username' => $username, 'password' => ResearchSeeder::PASSWORD];

// Guest, then admin
$send('guest', 'GET', '/research/dashboard');
$send('guest', 'GET', '/research/login');
$send('admin', 'POST', '/research/login', $credentials($adminName));
$send('admin', 'GET', '/research/courses');
$send('admin', 'GET', '/research/courses?page=10');
$send('admin', 'GET', "/research/courses/{$enrolledCourse}/enrollments");
$send('admin', 'GET', '/research/courses/9999/enrollments');
$send('admin', 'GET', '/research/reports/courses?period_id=1');
$send('admin', 'GET', '/research/reports/courses');
$send('admin', 'GET', '/research/dashboard');

// Student, with a fresh browser
$cookies = [];
$previous = null;
$send('guest', 'GET', '/research/login');
$send('student', 'POST', '/research/login', $credentials($studentName));
$send('student', 'GET', '/research/dashboard');
$send('student', 'GET', "/research/courses/{$enrolledCourse}/materials");
$send('student', 'GET', "/research/courses/{$otherCourse}/materials");
$send('student', 'GET', '/research/courses/9999/materials');
$send('student', 'GET', "/research/exams/{$otherExam}");
$send('student', 'GET', '/research/exams/99999');
$send('student', 'GET', "/research/exams/{$exam}");
$send('student', 'POST', "/research/exams/{$exam}/submissions", ['answers' => array_slice($answers, 0, 24)]);
$send('student', 'GET', "/research/exams/{$exam}");
$send('student', 'POST', "/research/exams/{$exam}/submissions", ['answers' => $answers]);
$send('student', 'GET', '/research/dashboard');
$send('student', 'GET', "/research/exams/{$exam}");
$send('student', 'POST', "/research/exams/{$exam}/submissions", ['answers' => $answers]);
$send('student', 'GET', '/research/courses');
