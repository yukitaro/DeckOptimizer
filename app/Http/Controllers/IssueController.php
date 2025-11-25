<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\DeckOptimizerIssues as Issue;
use App\Models\EnumValue;

class IssueController extends Controller
{
    public function index() {
        $this->authorize('viewAny', Issue::class);
        return Issue::all();
    }

    public function show(Issue $issue) {
        $this->authorize('view', $issue);
        return $issue;
    }

    public function store(Request $request) {
        $this->authorize('create', Issue::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string|in:Low,Medium,High,Critical',
            'type' => ['required', 'string', Rule::exists('enum_values', 'slug')->where(fn ($q) => $q->where('domain', 'issue_types'))],
        ]);

        $issueType = EnumValue::where('domain', 'issue_types')
                            ->where('slug', $request->input('type'))
                            ->firstOrFail();        

        $issue = Issue::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'issue_type_id' => $issueType->id,
            'issue_creator_id' => auth()->id(),
        ]);
        
        return response()->json($issue, 201);
    }

    public function update(Request $request, Issue $issue) {
        $this->authorize('update', $issue);
        // validate and update
    }

    public function destroy(Issue $issue) {
        $this->authorize('delete', $issue);
        $issue->delete();
    }
}
