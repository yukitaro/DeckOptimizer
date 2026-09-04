<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Permission;

class AdminPermissionController extends Controller
{
    public function index()
    {
        return Permission::all();
    }
}
