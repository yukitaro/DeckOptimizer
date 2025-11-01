<?php

namespace App\Http\Controllers\Auth;

use App\Models\InvitationToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class InviteRegistrationController extends Controller
{
    public function showForm(Request $request)
    {
        $token = $request->query('token');
        $invite = InvitationToken::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('auth.invite-register', ['email' => $invite->email, 'token' => $token]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required|string',
        ]);

        $invite = InvitationToken::where('token', $request->token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $invite->email,
            'password' => Hash::make($request->password),
        ]);

        $invite->update(['used' => true]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
