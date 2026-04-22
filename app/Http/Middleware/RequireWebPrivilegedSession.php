<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWebPrivilegedSession
{
    public const SESSION_MFA_KEY = 'admin.privileged.mfa_verified';

    public const SESSION_TRUSTED_DEVICE_KEY = 'admin.privileged.trusted_device_id';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isPrivileged()) {
            return $next($request);
        }

        $trustedDeviceId = $request->session()->get(self::SESSION_TRUSTED_DEVICE_KEY);
        $mfaVerified = $request->session()->get(self::SESSION_MFA_KEY, false) === true;

        if (! $user->mfa_enabled || ! $mfaVerified || ! is_numeric($trustedDeviceId)) {
            return $this->redirectToSecurity($request);
        }

        $trustedDevice = TrustedDevice::query()
            ->where('id', (int) $trustedDeviceId)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if ($trustedDevice === null) {
            $request->session()->forget([
                self::SESSION_MFA_KEY,
                self::SESSION_TRUSTED_DEVICE_KEY,
            ]);

            return $this->redirectToSecurity($request);
        }

        $trustedDevice->update(['last_used_at' => now()]);

        return $next($request);
    }

    private function redirectToSecurity(Request $request): Response
    {
        $request->session()->put('url.intended', $request->fullUrl());

        return redirect()
            ->route('admin.security.show')
            ->with('status', 'Privileged session verification is required to continue.');
    }
}
