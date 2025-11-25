<?php

return [
    'adminConsole' => ['view_admin_console'],
    'roleManagement' => ['manage_roles'],
    'permissionMatrix' => ['assign_permissions'],
    'mtgImport' => ['manage_import_candidates'],
    'mtgSetData' => ['view_set_data'],
    'cardMetadata' => ['view_card_metadata'],

    // Optional: legacy or shared permissions
    'issues' => ['view_issues', 'create_issues', 'edit_issues', 'delete_issues'],
    'users' => ['view_users', 'assign_roles'],
    'roles' => ['view_roles'],
    'enums' => ['manage_enums'],
];

