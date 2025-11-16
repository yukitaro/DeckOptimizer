<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Role;

class AdminRoleController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return Role::with('permissions')->get();
    }

    public function assignPermissions(Request $request, Role $role) {
        $this->authorize('update', $role); // Or gate by 'assign_permissions'
        $role->permissions()->sync($request->input('permission_ids'));
        return response()->json(['status' => 'updated']);
    }
}
