<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Session login of the research pages, the same for every implementation. A student
 * lands on the dashboard and the admin on the course list, as on the real pages.
 */
class LoginController extends Controller
{
    public function show(): View
    {
        return view('research.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['username' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        $isStudent = DB::table('students')->where('id', Auth::id())->exists();

        return redirect()->route($isStudent ? 'research.dashboard' : 'research.courses');
    }
}
