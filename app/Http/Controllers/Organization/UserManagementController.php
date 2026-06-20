<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteUserRequest;
use App\Http\Requests\Organization\UpdateUserRequest;
use App\Mail\InvitationMail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('role')
            ->when($request->search, fn ($query, $search) =>
                $query->where(fn ($inner) =>
                    $inner->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->when($request->role, fn ($query, $role) =>
                $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $role))
            )
            ->orderBy('name');

        $users = $query->paginate(15)->withQueryString()
            ->through(fn (User $user) => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'avatar'        => $user->avatar,
                'is_active'     => $user->is_active,
                'last_login_at' => $user->last_login_at?->diffForHumans(),
                'role'          => $user->role ? [
                    'id'           => $user->role->id,
                    'name'         => $user->role->name,
                    'display_name' => $user->role->display_name,
                ] : null,
                'created_at'    => $user->created_at->toIso8601String(),
            ]);

        return Inertia::render('organization/Users', [
            'users'   => $users,
            'roles'   => Role::all(['id', 'name', 'display_name']),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function invite(InviteUserRequest $request): RedirectResponse
    {
        $invitation = UserInvitation::create([
            'email'      => $request->email,
            'role_id'    => $request->role_id,
            'invited_by' => $request->user()->id,
            'token'      => UserInvitation::generateToken(),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return back()->with('toast', ['type' => 'success', 'message' => "Invitation sent to {$invitation->email}."]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->isOwner() && $request->user()->id === $user->id) {
            $ownerRole = Role::where('name', 'owner')->firstOrFail();
            if ($request->role_id !== $ownerRole->id) {
                return back()->withErrors(['role_id' => 'You cannot change your own role.']);
            }
        }

        $user->update([
            'name'    => $request->name,
            'phone'   => $request->phone,
            'role_id' => $request->role_id,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'User updated successfully.']);
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('toast', ['type' => 'success', 'message' => "Password reset email sent to {$user->email}."]);
        }

        return back()->with('toast', ['type' => 'error', 'message' => 'Could not send password reset email.']);
    }

    public function toggleStatus(User $user, Request $request): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $action = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('toast', ['type' => 'success', 'message' => "User {$action} successfully."]);
    }

    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            abort(403, 'You cannot delete your own account.');
        }

        if ($user->isOwner()) {
            $ownerCount = User::whereHas('role', fn ($query) => $query->where('name', 'owner'))->count();
            if ($ownerCount <= 1) {
                abort(403, 'Cannot delete the only owner account.');
            }
        }

        $user->delete();

        return to_route('organization.users')->with('toast', ['type' => 'success', 'message' => 'User deleted successfully.']);
    }
}
