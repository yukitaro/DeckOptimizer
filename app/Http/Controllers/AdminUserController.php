<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminUserController extends Controller
{
    use AuthorizesRequests;

    public function index() {
        $this->authorize('viewAny', User::class); 
        return User::with('roles')->get();
    }

    public function assignRoles(Request $request, User $user) {
        $this->authorize('update', $user); // Or gate by 'assign_roles'
        $user->roles()->sync($request->input('role_ids'));
        return response()->json(['status' => 'updated']);
    }
}
