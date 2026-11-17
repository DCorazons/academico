<?php

/*
 * Equivalence check (thesis proposal, section 3.2.2): sends requests to every
 * endpoint under test through the HTTP kernel, in a fixed order, and prints the
 * status, the SQL with its bindings, and the body of each response. Memory is left
 * out because it differs between runs. Run by capture.sh on a freshly seeded
 * database; the outputs of the implementations are compared by compare.sh.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Fixed clock, so that the timestamps of the stored submission are the same
Carbon::setTestNow('2026-11-17 08:00:00');

$admin = config('research.api_token');
$studentId = 35;
$student = DB::table('users')->where('id', $studentId)->value('api_token');
$enrolledCourse = DB::table('enrollments')->where('student_id', $studentId)->orderBy('course_id')->value('course_id');
$otherCourse = DB::table('courses')
    ->whereNotIn('id', DB::table('enrollments')->where('student_id', $studentId)->select('course_id'))
    ->orderBy('id')
    ->value('id');
$exam = DB::table('exams')->where('course_id', $enrolledCourse)->orderBy('id')->value('id');
$otherExam = DB::table('exams')->where('course_id', $otherCourse)->orderBy('id')->value('id');

// 20 correct answers and 5 wrong ones
$answers = [];
foreach (DB::table('exam_questions')->where('exam_id', $exam)->orderBy('id')->pluck('correct_option', 'id') as $id => $key) {
    $answers[] = ['question_id' => $id, 'option' => count($answers) < 20 ? $key : ($key === 'A' ? 'B' : 'A')];
}

$cases = [
    ['GET', '/api/courses', $admin, null],
    ['GET', '/api/courses?page=10', $admin, null],
    ['GET', "/api/courses/{$enrolledCourse}/enrollments", $admin, null],
    ['GET', '/api/courses/9999/enrollments', $admin, null],
    ['GET', '/api/me/dashboard', $student, null],
    ['GET', "/api/courses/{$enrolledCourse}/materials", $student, null],
    ['GET', "/api/courses/{$otherCourse}/materials", $student, null],
    ['GET', '/api/courses/9999/materials', $student, null],
    ['GET', "/api/exams/{$exam}", $student, null],
    ['GET', "/api/exams/{$otherExam}", $student, null],
    ['GET', '/api/exams/99999', $student, null],
    ['POST', "/api/exams/{$exam}/submissions", $student, ['answers' => array_slice($answers, 0, 24)]],
    ['POST', "/api/exams/{$exam}/submissions", $student, ['answers' => $answers]],
    ['POST', "/api/exams/{$exam}/submissions", $student, ['answers' => $answers]],
    ['POST', "/api/exams/{$otherExam}/submissions", $student, ['answers' => $answers]],
    ['POST', "/api/exams/{$exam}/submissions", $student, ['answers' => [['question_id' => 1, 'option' => 'E']]]],
    ['GET', '/api/me/dashboard', $student, null],
    ['GET', '/api/reports/courses?period_id=1', $admin, null],
    ['GET', '/api/reports/courses', $admin, null],
    ['GET', '/api/reports/courses?period_id=99', $admin, null],
    ['GET', '/api/me/dashboard', $admin, null],
    ['GET', '/api/courses', $student, null],
    ['GET', '/api/courses', null, null],
];

foreach ($cases as [$method, $uri, $token, $body]) {
    // Each request starts like a new process: no user kept by the guard
    $app['auth']->forgetGuards();

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries) {
        $queries[] = $query->sql.' | '.json_encode($query->bindings);
    });

    $server = ['CONTENT_TYPE' => 'application/json'] + ($token ? ['HTTP_AUTHORIZATION' => "Bearer {$token}"] : []);
    $request = Request::create($uri, $method, [], [], [], $server, $body ? json_encode($body) : null);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $app['events']->forget(QueryExecuted::class);

    printf("### %s %s -> %d, %d queries\n", $method, $uri, $response->getStatusCode(), count($queries));
    foreach ($queries as $query) {
        echo "SQL {$query}\n";
    }
    echo 'BODY '.str_replace("\n", '', $response->getContent())."\n";
}
