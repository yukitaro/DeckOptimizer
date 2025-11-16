<?php

namespace App\Enums;

enum IssueType: string
{
    case Bug = 'bug';
    case FeatureRequest = 'feature_request';
    case Improvement = 'improvement';
    case Wishlist = 'wishlist';
}