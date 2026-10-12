<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.view'), 403);
        $users = User::with('role')->orderBy('name')->paginate($this->perPage($request))->withQueryString();
        $roles = $this->assignableRoles($request->user());
        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Super Admin creates any user (including other Super Admins); the Commissioner
     * every role except Super Admin and Commissioner; the Permanent Secretary every
     * role except Super Admin, Commissioner and Permanent Secretary.
     * New accounts start active and must change their password at first sign-in.
     */
    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('users.create'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role_id' => ['required', Rule::in($this->assignableRoles($request->user())->pluck('id')->all())],
            'contractor_id' => 'nullable|exists:contractors,id',
            'department' => 'nullable|string|max:255',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        $data['status'] = 'active';
        $data['is_active'] = true;
        $data['must_change_password'] = true;

        User::create($data);

        return back()->with('status', 'User created.');
    }

    /**
     * Activate / deactivate / suspend / blacklist a user.
     * Only 'active' users can sign in. The Permanent Secretary can do this for
     * everyone except the Super Admin and the Commissioner.
     */
    public function setStatus(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('users.status'), 403);
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own account status.');
        abort_unless($request->user()->canManageUser($user), 403, 'You are not allowed to change this account.');

        $data = $request->validate(['status' => 'required|in:'.implode(',', User::STATUSES)]);

        $user->update([
            'status' => $data['status'],
            'is_active' => $data['status'] === 'active',
        ]);

        return back()->with('status', "{$user->name} is now {$data['status']}.");
    }

    /**
     * Admin resets another user's password (e.g. when it's forgotten).
     */
    public function resetPassword(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermission('users.reset_password'), 403);
        abort_unless($request->user()->canManageUser($user), 403, 'You are not allowed to change this account.');

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);

        // They must replace this administrator-set password at their next sign-in.
        $user->update(['password' => $data['password'], 'must_change_password' => true]);

        return back()->with('status', "Password reset for {$user->name}. Share it with them securely — they will be asked to set their own password when they sign in.");
    }

    /**
     * Roles the signed-in user may hand out (retired roles never appear):
     *  - Super Admin: every role, including Super Admin
     *  - Commissioner: every role except Super Admin and Commissioner
     *  - Permanent Secretary: every role except Super Admin, Commissioner and Permanent Secretary
     */
    private function assignableRoles(User $actor)
    {
        $excluded = match ($actor->role?->name) {
            Role::SUPER_ADMIN => [],
            Role::COMMISSIONER => [Role::SUPER_ADMIN, Role::COMMISSIONER],
            default => [Role::SUPER_ADMIN, Role::COMMISSIONER, Role::PERMANENT_SECRETARY],
        };

        return Role::assignable()->whereNotIn('name', $excluded)->orderBy('display_name')->get();
    }
}
