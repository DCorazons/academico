<?php

namespace Tests\Feature\Research;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * The parts of the server-rendered research pages that are the same in every
 * implementation: session login, guest redirect, and the Blade views fed with the
 * arrays of the API resources. The page controllers live in the implementation branches.
 */
class ResearchPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertUser(1, 'admin');
        $this->insertUser(2, 'student0001');
        DB::table('students')->insert(['id' => 2]);
    }

    public function test_guests_are_sent_to_the_research_login(): void
    {
        $this->get('/research/dashboard')->assertRedirect('/research/login');
        $this->get('/research/courses')->assertRedirect('/research/login');
    }

    public function test_the_login_page_renders_a_form_with_a_csrf_token(): void
    {
        $this->get('/research/login')
            ->assertOk()
            ->assertSee('name="_token"', false)
            ->assertSee('name="username"', false);
    }

    public function test_a_student_lands_on_the_dashboard_and_the_admin_on_the_courses(): void
    {
        $this->post('/research/login', ['username' => 'student0001', 'password' => 'secret'])
            ->assertRedirect('/research/dashboard');
        $this->assertAuthenticatedAs(User::find(2));

        $this->post('/logout');
        $this->app['auth']->forgetGuards();

        $this->post('/research/login', ['username' => 'admin', 'password' => 'secret'])
            ->assertRedirect('/research/courses');
        $this->assertAuthenticatedAs(User::find(1));
    }

    public function test_a_wrong_password_returns_to_the_form_with_an_error(): void
    {
        $this->from('/research/login')
            ->post('/research/login', ['username' => 'admin', 'password' => 'wrong'])
            ->assertRedirect('/research/login')
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_the_admin_views_render_the_resource_arrays(): void
    {
        $this->actingAs(User::find(1));

        $course = [
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'volume' => 48, 'spots' => 50,
            'start_date' => '2026-08-17', 'end_date' => '2027-01-15',
            'period' => ['id' => 1, 'name' => 'Ganjil 2026/2027'],
            'teacher' => ['id' => 2, 'firstname' => 'Budi', 'lastname' => 'SANTOSO'],
        ];
        $page = new LengthAwarePaginator([$course], 21, 20, 1, ['path' => '/research/courses']);

        $html = view('research.admin.courses', ['page' => $page, 'courses' => [$course]])->render();
        $this->assertStringContainsString('Semester 1 Kelas 01', $html);
        $this->assertStringContainsString('Budi SANTOSO', $html);
        $this->assertStringContainsString('/research/courses?page=2', $html);

        $html = view('research.admin.enrollments', ['courseId' => 1, 'enrollments' => [[
            'id' => 1,
            'student' => ['id' => 35, 'idnumber' => '2600000001', 'firstname' => 'Siti', 'lastname' => 'AMINAH'],
            'status' => ['id' => 2, 'name' => 'PAYÉ'],
        ]]])->render();
        $this->assertStringContainsString('2600000001', $html);
        $this->assertStringContainsString('PAYÉ', $html);

        $html = view('research.admin.report', ['periodId' => 1, 'courses' => [
            ['id' => 1, 'name' => 'Semester 1 Kelas 01', 'enrollments_count' => 40],
            ['id' => 2, 'name' => 'Semester 3 Kelas 01', 'enrollments_count' => 41],
        ]])->render();
        $this->assertStringContainsString('81', $html);
    }

    public function test_the_student_views_render_the_resource_arrays(): void
    {
        $this->actingAs(User::find(2));
        // Shared by the ShareErrorsFromSession middleware on a real request
        view()->share('errors', new ViewErrorBag);

        $html = view('research.student.dashboard', ['courses' => [[
            'id' => 1, 'name' => 'Semester 1 Kelas 01', 'materials_count' => 14,
            'exams' => [
                ['id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01', 'submitted' => true],
                ['id' => 2, 'type' => 'UAS', 'title' => 'UAS Semester 1 Kelas 01', 'submitted' => false],
            ],
        ]]])->render();
        $this->assertStringContainsString('14 materi', $html);
        $this->assertStringContainsString('Sudah dikerjakan', $html);
        $this->assertStringContainsString('/research/exams/2', $html);

        $html = view('research.student.materials', ['courseId' => 1, 'materials' => [
            ['id' => 1, 'meeting' => 1, 'title' => 'Pertemuan 1: Pengantar', 'content' => "Baris satu\nBaris <dua>"],
        ]])->render();
        $this->assertStringContainsString('Baris satu<br />', $html);
        $this->assertStringContainsString('Baris &lt;dua&gt;', $html);

        $html = view('research.student.exam', ['exam' => [
            'id' => 1, 'course_id' => 1, 'type' => 'UTS', 'title' => 'UTS Semester 1 Kelas 01',
            'questions' => [[
                'id' => 7, 'number' => 1, 'question' => 'Apa itu MVC?',
                'options' => ['A' => 'Pola arsitektur', 'B' => 'Basis data', 'C' => 'Bahasa', 'D' => 'Server'],
            ]],
        ]])->render();
        $this->assertStringContainsString('name="answers[0][question_id]" value="7"', $html);
        $this->assertStringContainsString('name="answers[0][option]" value="D"', $html);
        $this->assertStringContainsString('/research/exams/1/submissions', $html);
    }

    private function insertUser(int $id, string $username): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'username' => $username,
            'firstname' => $username,
            'lastname' => 'Test',
            'email' => "{$username}@academico.test",
            'password' => Hash::make('secret'),
            'locale' => 'en',
        ]);
    }
}
