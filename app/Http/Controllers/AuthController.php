<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $throttleKey = strtolower($request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.']);
        }

        $account = User::where('email', $credentials['email'])->first();

        if (! $account || ! Hash::check($credentials['password'], $account->password)) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        // Correct password but the account is not Active: say why (only after the
        // password is verified, so the status of an account is never revealed to strangers).
        if ($account->status !== 'active') {
            RateLimiter::clear($throttleKey);
            return back()->withErrors(['email' => User::statusMessage($account->status)])->onlyInput('email');
        }

        Auth::login($account, $request->boolean('remember'));

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        LoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'logged_in_at' => now(),
        ]);

        if ($user->must_change_password) {
            return redirect()->route('profile.password.edit')
                ->with('warning', 'Welcome! Please set a personal password before you continue.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
