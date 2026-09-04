<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\EnumService;

// app/Http/Controllers/EnumController.php
class EnumController extends Controller
{
    public function index(EnumService $enums)
    {
        $payload = $enums->getBootstrapEnums(); // array: data + versions
        return response()->json($payload);
    }

    public function store(Request $request, string $domain)
    {
        // optional: explicit check in addition to route middleware
        $this->authorize('manage_enums');

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'sort' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $actorId = $request->user()?->id ?? 0;

        $row = app(EnumService::class)->upsert($domain, $validated, $actorId);

        return response()->json([
            'data' => $row,
            'version' => $row->version,
        ]);
    }    
}
