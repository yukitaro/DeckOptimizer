<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use App\Enums\IssueType;
use App\Services\EnumService;

class IssueEnumController extends Controller
{
    public function __construct(private EnumService $enums)
    {
        // optional middleware here, but route-level can handle auth/ability
    }

    public function index(Request $request)
    {
        $issueTypes = $this->enums->list('issue_types', true)
            ->map(fn($r) => [
                'id' => $r->id,
                'slug' => $r->slug,
                'label' => $r->label,
                'meta' => $r->metadata,
                'version' => $r->version,
            ]);

        return response()->json([
            'data' => ['issue_types' => $issueTypes],
            'version' => $issueTypes->max('version') ?? 0,
        ]);
    }

    /**
     * POST /api/admin/issue-types
     * body: { slug, label, metadata?, sort?, active? }
     */
    public function store(Request $request)
    {
        $this->authorize('manage_issue_types');

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'sort' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
        ]);

        $actorId = $request->user()?->id ?? 0;

        $row = $this->enums->createOrUpdateIssueType($validated, $actorId);

        return response()->json([
            'data' => [
                'id' => $row->id,
                'slug' => $row->slug,
                'label' => $row->label,
                'meta' => $row->metadata,
                'sort' => $row->sort,
                'active' => (bool) $row->active,
            ],
            'version' => $row->version,
        ]);
    }
}