<?php

return [
    'plugin' => [
        'name' => 'User Permissions',
        'description' => 'Front-end user permissions management.',
        'access_permissions' => 'Manage Permissions',
        'access_group_permissions' => 'Manage User Group Permissions',
        'access_user_permissions' => 'Manage User Permissions',
        'tab' => 'User Permissions'
    ],
    'permissions' => [
        'menu_label' => 'Permissions',
        'menu_desc' => 'Choose the permissions',
        'view_permission' => 'View Permission',
        'create_permission' => 'New Permission',
        'update_permission' => 'Update Permission',
        'delete_permission' => 'Delete Permission',
        'delete_confirm' => 'Do you really want to delete this permission?',
    ],
    'permission' => [
        'model_id' => 'Id',
        'model_code' => 'Code',
        'model_name' => 'Name',
        'model_desc' => 'Description',
        'delete_permission' => 'Delete Permission',
    ],
];
