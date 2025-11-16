<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\IssueType;

class DeckOptimizerIssues extends Model
{
    protected $table = 'deck_optimizer_issues';

    protected $fillable = [
        'issue_creator_id',
        'issue_assignee_id',
        'title',
        'description',
        'issue_type',
        'priority',
        'status',
    ];

    protected $casts = [
        'issue_type' => IssueType::class,
    ]
}