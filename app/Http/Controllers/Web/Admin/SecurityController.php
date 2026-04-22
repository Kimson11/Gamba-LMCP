<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireWebPrivilegedSession;
use App\Http\Requests\Web\Admin\ActivatePrivilegedSessionRequest;
use App\Models\TrustedDevice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SecurityController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = request()->user();

        abort_unless($user !== null, 401);

        if (! $user->isPrivileged()) {
            return redirect()->route('admin.dashboard');
        }

        $trustedDevices = $user->trustedDevices()
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->latest('last_used_at')
            ->get();

        return view('admin.security.show', [
            'trustedDevices' => $trustedDevices,
            'privilegedSessionActive' => request()->session()->get(RequireWebPrivilegedSession::SESSION_MFA_KEY, false) === true,
        ]);
    }

    public function store(ActivatePrivilegedSessionRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        if (! $user->isPrivileged()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->mfa_enabled) {
            return back()->withErrors([
                'trusted_device_id' => 'MFA must be enabled before a privileged session can be activated.',
            ]);
        }

        $trustedDevice = TrustedDevice::query()
            ->where('id', (int) $request->validated('trusted_device_id'))
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if ($trustedDevice === null) {
            return back()->withErrors([
                'trusted_device_id' => 'Select an active trusted device.',
            ]);
        }

        $request->session()->put(RequireWebPrivilegedSession::SESSION_MFA_KEY, true);
        $request->session()->put(RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY, $trustedDevice->id);

        $trustedDevice->update(['last_used_at' => now()]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        request()->session()->forget([
            RequireWebPrivilegedSession::SESSION_MFA_KEY,
            RequireWebPrivilegedSession::SESSION_TRUSTED_DEVICE_KEY,
        ]);

        return redirect()
            ->route('admin.security.show')
            ->with('status', 'Privileged session cleared.');
    }
}
