<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Enums\IssueType;
use App\Models\EnumValue;
use App\Models\SiteFeatures;
use App\Models\User;

class DeckOptimizerIssues extends Model
{
    protected $table = 'deck_optimizer_issues';

    public function issueType()
    {
        return $this->belongsTo(EnumValue::class, 'issue_type_id');
    }

    public function siteFeature()
    {
        return $this->belongsTo(SiteFeatures::class, 'site_feature_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'issue_creator_id');
    }    

    protected $fillable = [
        'issue_creator_id',
        'issue_assignee_id',
        'title',
        'description',
        'issue_type_id',
        'priority',
        'status',
        'site_feature_id',
    ];
}