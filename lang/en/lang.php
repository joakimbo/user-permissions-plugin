<?php

return [
    'plugin' => [
        'name' => 'User Permissions',
        'description' => 'Front-end user permissions management.',
        'access_permissions' => 'Manage Permissions',
        'access_group_permissions' => 'Manage User Group Permissions',
        'access_user_permissions' => 'Manage User Permissions',
    ],
    'permissions' => [
        'menu_label' => 'Permissions',
        'menu_desc' => 'Choose the permissions',
        'create_permission' => 'Create Permission',
        'delete_permission' => 'Delete Permission',
    ],
    'permission' => [
        'model_id' => 'ID',
        'model_name' => 'Name',
        'model_desc' => 'Description',
        'delete_permission' => 'Delete Permission',
    ],
];
