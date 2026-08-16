<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Site Features Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for site features.
    | You can define default settings and options for various features
    | available on the site.
    |
    */
    'mtg' => [
        'search' => [
            'feature_name' => 'Search',
            'description'  => 'Advanced card search functionality.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 1,
            'meta'         => null,
        ],
        'deck-management' => [
            'feature_name' => 'Deck Management',
            'description'  => 'Create and manage your decks online.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 2,
        ],
        'collection-management' => [
            'feature_name' => 'Collection Management',
            'description'  => 'Track and manage your card collection.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 3,
        ],
        'card-metadata' => [
            'feature_name' => 'Card Metadata',
            'description'  => 'Detailed metadata for each card.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 4,
        ],
        'pauper-deck-comparison' => [
            'feature_name' => 'Pauper Deck Comparison',
            'description'  => 'Compare your deck against popular Pauper decks.',
            'is_enabled'   => false,
            'is_global'    => false,
            'sort_order'   => 5,
        ],
        'shopping-list' => [
            'feature_name' => 'Shopping List',
            'description'  => 'Generate shopping lists for missing cards.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 6,
        ],
        'user-dashboard' => [
            'feature_name' => 'User Dashboard',
            'description'  => 'Personalized dashboard for users.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 7,
        ],
        'admin-dashboard' => [
            'feature_name' => 'Admin Dashboard',
            'description'  => 'Administrative tools and analytics.',
            'is_enabled'   => false,
            'is_global'    => false,
            'sort_order'   => 8,
        ],
        'settings-page' => [
            'feature_name' => 'Settings Page',
            'description'  => 'User settings and preferences management.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 9,
        ],
        'sitewide' => [
            'feature_name' => 'Sitewide Features',
            'description'  => 'Features that apply sitewide.',
            'is_enabled'   => true,
            'is_global'    => true,
            'sort_order'   => 10,
        ],
        'uncategorized' => [
            'feature_name' => 'Uncategorized Features',
            'description'  => 'Miscellaneous features not categorized elsewhere.',
            'is_enabled'   => false,
            'is_global'    => false,
            'sort_order'   => 11,
        ],
    ]
];