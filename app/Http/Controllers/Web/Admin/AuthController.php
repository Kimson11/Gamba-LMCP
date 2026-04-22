<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireWebPrivilegedSession;
use App\Http\Requests\Web\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()?->role->canAccessAdminPortal()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, false)) {
            return back()
                ->withErrors([
                    'email' => 'The provided credentials could not be verified.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        if ($user === null || ! $user->role->canAccessAdminPortal()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => 'This account is not allowed to access the admin portal.',
                ])
                ->onlyInput('email');
        }

        $request->session()->forget([
            RequireWebPrivilegedSession::SESSION_MFA_KEY,
            RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY,
        ]);

        if ($user->isPrivileged()) {
            return redirect()->route('admin.security.show');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        request()->session()->forget([
            RequireWebPrivilegedSession::SESSION_MFA_KEY,
            RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY,
        ]);

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
