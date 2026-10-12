<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Any signed-in user can change their own password.
     */
    public function editPassword()
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->mixedCase()->numbers()],
        ], [
            'password.different' => 'Your new password must be different from your current password.',
        ]);

        $request->user()->update([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('status', 'Your password has been changed.');
    }
}
