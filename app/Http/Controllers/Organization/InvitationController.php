<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = UserInvitation::with('role')
            ->where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return Inertia::render('organization/InvitationExpired');
        }

        return Inertia::render('organization/AcceptInvitation', [
            'invitation' => [
                'token'      => $invitation->token,
                'email'      => $invitation->email,
                'role'       => $invitation->role->display_name,
                'expires_at' => $invitation->expires_at->toIso8601String(),
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = UserInvitation::with('role')
            ->where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return to_route('invitation.show', ['token' => $token]);
        }

        $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'password'              => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $invitation->email,
            'password'          => Hash::make($request->password),
            'role_id'           => $invitation->role_id,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        $invitation->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
