<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs on every web request of a signed-in user:
 *  1. An account that is no longer Active (deactivated / suspended / blacklisted)
 *     is signed out at once, with its status shown on the sign-in page.
 *  2. An account that still has its default (or administrator-set) password is
 *     sent to "Change password" until it sets a personal one.
 */
class EnsureAccountIsUsable
{
    private const ALLOWED_WHILE_CHANGING = ['profile.password.edit', 'profile.password.update', 'logout', 'login'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => User::statusMessage($user->status)]);
        }

        if ($user && $user->must_change_password
            && ! $request->routeIs(...self::ALLOWED_WHILE_CHANGING)
            && ! $request->routeIs('help.*')) {
            return redirect()->route('profile.password.edit')
                ->with('warning', 'Please set a personal password before you continue. Your current password was issued by an administrator.');
        }

        return $next($request);
    }
}
