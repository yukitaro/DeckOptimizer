<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Issue;

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
        // validate and create
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
