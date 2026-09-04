<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Models\DeckOptimizerIssues as Issue;
use App\Models\EnumValue;
use App\Models\SiteFeatures;

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
            'type' => [
                'required',
                'string',
                Rule::exists('enum_values', 'slug')->where(fn ($q) => $q->where('domain', 'issue_types'))
            ],
            // optional site feature fields
            'site_mode' => 'nullable|string',
            'site_feature_slug' => 'nullable|string',
        ]);

        // Resolve issue type
        $issueType = EnumValue::where('domain', 'issue_types')
                            ->where('slug', $validated['type'])
                            ->firstOrFail();

        // Resolve site_feature_id if both site_mode and site_feature_slug provided
        $siteFeatureId = null;
        if (!empty($validated['site_mode']) && !empty($validated['site_feature_slug'])) {
            $feature = SiteFeatures::where('site_mode', $validated['site_mode'])
                                ->where('slug', $validated['site_feature_slug'])
                                ->first();

            if (! $feature) {
                return response()->json([
                    'message' => 'The selected feature does not exist for the provided site mode.'
                ], 422);
            }

            // optional: ensure feature is enabled
            if (! $feature->is_enabled) {
                return response()->json([
                    'message' => 'The selected feature is currently disabled.'
                ], 422);
            }

            $siteFeatureId = $feature->id;
        } elseif (!empty($validated['site_feature_slug']) && empty($validated['site_mode'])) {
            // policy decision: require site_mode when providing a slug
            return response()->json([
                'message' => 'site_mode is required when providing site_feature_slug.'
            ], 422);
        }

        $issue = Issue::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'issue_type_id' => $issueType->id,
            'issue_creator_id' => auth()->id(),
            'site_feature_id' => $siteFeatureId,
        ]);

        // eager load relation for client convenience
        $issue->load('siteFeature', 'issueType', 'creator');

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
