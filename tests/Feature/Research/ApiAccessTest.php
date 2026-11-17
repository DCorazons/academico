<?php

namespace Tests\Feature\Research;

use App\Http\Middleware\RecordPeakMemory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Checks the middleware stack shared by the endpoints under test, on probe routes
 * that use the same stack, since the real controllers live in the implementation
 * branches.
 */
class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_TOKEN = 'admin-token';

    private const STUDENT_TOKEN = 'student-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertUser(1, 'admin', self::ADMIN_TOKEN);
        $this->insertUser(2, 'student', self::STUDENT_TOKEN);
        DB::table('students')->insert(['id' => 2]);

        Route::middleware(['peak_memory', 'auth:api', 'api_role:admin'])
            ->get('/api/_probe/admin', fn () => response()->json(['ok' => true]));
        Route::middleware(['peak_memory', 'auth:api', 'api_role:student'])
            ->get('/api/_probe/student', fn () => response()->json(['ok' => true]));
    }

    public function test_requests_without_a_token_get_a_json_401(): void
    {
        $response = $this->get('/api/_probe/student');

        $response->assertStatus(401)->assertJson(['message' => 'Unauthenticated.']);
        $this->assertTrue($response->headers->has(RecordPeakMemory::HEADER));
    }

    public function test_requests_with_an_unknown_token_get_a_401(): void
    {
        $this->withToken('unknown')->get('/api/_probe/admin')->assertStatus(401);
    }

    public function test_each_token_reaches_its_own_endpoints(): void
    {
        $this->withToken(self::ADMIN_TOKEN)->get('/api/_probe/admin')->assertOk();

        // The test application keeps the resolved guard, and its user, between requests
        $this->app['auth']->forgetGuards();

        $this->withToken(self::STUDENT_TOKEN)->get('/api/_probe/student')->assertOk();
    }

    public function test_admin_token_on_a_student_endpoint_gets_a_json_403(): void
    {
        $this->withToken(self::ADMIN_TOKEN)->get('/api/_probe/student')
            ->assertStatus(403)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader(RecordPeakMemory::HEADER);
    }

    public function test_student_token_on_an_admin_endpoint_gets_a_403(): void
    {
        $this->withToken(self::STUDENT_TOKEN)->get('/api/_probe/admin')->assertStatus(403);
    }

    public function test_successful_responses_carry_the_peak_memory_header(): void
    {
        $response = $this->withToken(self::STUDENT_TOKEN)->get('/api/_probe/student');

        $this->assertMatchesRegularExpression('/^\d+$/', $response->headers->get(RecordPeakMemory::HEADER));
    }

    public function test_authentication_and_role_check_take_two_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->withToken(self::STUDENT_TOKEN)->get('/api/_probe/student')->assertOk();

        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertCount(2, $queries, implode(PHP_EOL, $queries));
        $this->assertStringContainsString('"api_token" = ?', $queries[0]);
        $this->assertStringContainsString('from "students"', $queries[1]);
    }

    private function insertUser(int $id, string $username, string $token): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'username' => $username,
            'firstname' => $username,
            'lastname' => 'Test',
            'email' => "{$username}@academico.test",
            'password' => 'unused',
            'api_token' => $token,
        ]);
    }
}
