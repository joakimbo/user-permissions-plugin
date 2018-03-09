<?php namespace JBonnyDev\UserPermissions;

use Exception;
use Event;
use Backend;
use JBonnyDev\UserPermissions\Models\Permission as PermissionModel;
use Rainlab\User\Models\User as UserModel;
use Rainlab\User\Models\UserGroup as UserGroupModel;

class Plugin extends \System\Classes\PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = ['RainLab.User'];

    public function pluginDetails()
    {
        return [
            'name' => 'jbonnydev.userpermissions::lang.plugin.name',
            'description' => 'jbonnydev.userpermissions::lang.plugin.description',
            'author' => 'JoakimBo',
            'icon' => 'icon-unlock-alt'
        ];
    }

    public function registerPermissions()
    {
        return [
            'jbonnydev.userpermissions.access_permissions' => [
                'tab'   => 'rainlab.user::lang.plugin.tab',
                'label' => 'jbonnydev.userpermissions::lang.plugin.access_permissions'
            ],
            'jbonnydev.userpermissions.access_user_permissions' => [
                'tab'   => 'rainlab.user::lang.plugin.tab',
                'label' => 'jbonnydev.userpermissions::lang.plugin.access_user_permissions'
            ],
            'jbonnydev.userpermissions.access_group_permissions' => [
                'tab'   => 'rainlab.user::lang.plugin.tab',
                'label' => 'jbonnydev.userpermissions::lang.plugin.access_group_permissions'
            ],
        ];
    }

    public function boot()
    {
        $this->extendRainlabUserSideMenu();
        $this->extendUserModel();
        $this->extendUserGroupModel();
        $this->extendUserController();
        $this->extendUserGroupController();
    }

    protected function extendRainlabUserSideMenu()
    {
        Event::listen('backend.menu.extendItems', function($manager)
        {
            $manager->addSideMenuItems('RainLab.User', 'user', [
                'permissions' => [
                    'label' => 'jbonnydev.userpermissions::lang.permissions.menu_label',
                    'icon' => 'icon-unlock-alt',
                    'permissions' => ['jbonnydev.userpermissions.access_permissions'],
                    'url' => Backend::url('jbonnydev/userpermissions/permissions'),
                ]
            ]);
        });
    }

    protected function extendUserModel()
    {
        UserModel::extend(function($model)
        {

            $model->addPurgeable('userpermissioneditor');

            $model->belongsToMany['user_permissions'] = ['JBonnyDev\UserPermissions\Models\Permission',
                'table' => 'jbonnydev_userpermissions_user_permission',
                'key' => 'user_id',
                'otherKey' => 'permission_id',
                'timestamps' => true,
                'pivot' => ['permission_state'],
            ];

            $model->bindEvent('model.afterCreate', function() use ($model)
            {
                $permissions = PermissionModel::all();
                if($permissions)
                {
                    foreach($permissions as $permission)
                    {
                        $model->user_permissions()->attach($permission->id, ['permission_state' => 'inherit']);
                    }
                }

            });

            $model->bindEvent('model.afterSave', function() use ($model)
            {
                $permissionsInput = $model->getOriginalPurgeValue('userpermissioneditor');
                if($permissionsInput)
                {
                    foreach($permissionsInput as $permissionId => $permissionState)
                    {
                        $pivot = $model->user_permissions()->where('id', $permissionId)->first()->pivot;
                        switch($permissionState)
                        {
                            case 'allow':
                                $pivot->permission_state = 'allow';
                                break;
                            case 'inherit':
                                $pivot->permission_state = 'inherit';
                                break;
                            case 'deny':
                                $pivot->permission_state = 'deny';
                                break;
                            default:
                        }
                        $pivot->save();
                    }
                }
            });

            $model->addDynamicMethod('getAllowedPermissions', function() use ($model)
            {
                if(!$model->is_activated)
                {
                    return [];
                }

                $allowed_permissions = $model->user_permissions()
                    ->where('permission_state', 'allow')->lists('name','id');
                if(!$allowed_permissions)
                {
                    $allowed_permissions = [];
                }
                $inherit_permissions = $model->user_permissions()
                    ->where('permission_state', 'inherit')->lists('name','id');
                if(!$inherit_permissions)
                {
                    $inherit_permissions = [];
                }

                if($inherit_permissions)
                {
                    $groups = $model->groups;
                    if($groups)
                    {
                        $groups_allowed_permissions = [];
                        foreach($groups as $group)
                        {
                            $group_allowed_permissions = $group->user_permissions()
                                ->where('permission_state', 'allow')->lists('name','id');
                            $groups_allowed_permissions = array_merge($groups_allowed_permissions,
                                array_diff($group_allowed_permissions, $groups_allowed_permissions));
                        }
                        $inherited_allowed_permissions = array_intersect($inherit_permissions,
                                                                         $groups_allowed_permissions);
                        if($inherited_allowed_permissions)
                        {
                            foreach($inherited_allowed_permissions as $permissionId => $permissionName)
                            {
                                $allowed_permissions[$permissionId] = $permissionName;
                            }
                        }
                    }
                }
                return $allowed_permissions;
            });

            $model->addDynamicMethod('hasUserPermissionWithName', function($permissionName) use ($model)
            {
                return $model->hasUserPermissionsWithNames([$permissionName]);
            });

            $model->addDynamicMethod('hasUserPermissionsWithNames', function(array $permissionNames) use ($model)
            {
                foreach($permissionNames as $permissionName)
                {
                    if(!is_string($permissionName))
                    {
                        return false;
                    }
                }
                $allowed_permissions = $model->getAllowedPermissions();
                $hasAllPermissions = true;
                foreach($permissionNames as $permissionName)
                {
                    if(!in_array($permissionName, $allowed_permissions))
                    {
                        $hasAllPermissions = false;
                    }
                }
                return $hasAllPermissions;
            });

            $model->addDynamicMethod('hasUserPermissionWithId', function($permissionId) use ($model)
            {
                return $model->hasUserPermissionsWithIds([$permissionId]);
            });

            $model->addDynamicMethod('hasUserPermissionsWithIds', function(array $permissionIds) use ($model)
            {
                foreach($permissionIds as $permissionId)
                {
                    if(!is_int($permissionId))
                    {
                        return false;
                    }
                }
                $allowed_permissions = $model->getAllowedPermissions();
                $hasAllPermissions = true;
                foreach($permissionIds as $permissionId)
                {
                    if(!array_key_exists($permissionId, $allowed_permissions))
                    {
                        $hasAllPermissions = false;
                    }
                }
                return $hasAllPermissions;
            });

        });
    }

    protected function extendUserGroupModel()
    {
        UserGroupModel::extend(function($model)
        {
            $model->implement[] = 'JBonnyDev.UserPermissions.Behaviors.Purgeable';
            $model->addDynamicProperty('purgeable', ['userpermissioneditor','purgeable']);
            $model->belongsToMany['user_permissions'] = ['JBonnyDev\UserPermissions\Models\Permission',
                'table' => 'jbonnydev_userpermissions_group_permission',
                'key' => 'group_id',
                'otherKey' => 'permission_id',
                'timestamps' => true,
                'pivot' => ['permission_state'],
            ];

            $model->bindEvent('model.afterCreate', function() use ($model)
            {
                $permissions = PermissionModel::all();
                if($permissions)
                {
                    foreach($permissions as $permission)
                    {
                        $model->user_permissions()->attach($permission->id, ['permission_state' => 'deny']);
                    }
                }
            });

            $model->bindEvent('model.afterSave', function() use ($model)
            {
                $permissionsInput = $model->getOriginalPurgeValue('userpermissioneditor');
                if($permissionsInput)
                {
                    foreach($permissionsInput as $permissionId => $permissionState)
                    {
                        $pivot = $model->user_permissions()->where('id', $permissionId)->first()->pivot;
                        switch($permissionState)
                        {
                            case 'allow':
                                $pivot->permission_state = 'allow';
                                break;
                            case 'deny':
                                $pivot->permission_state = 'deny';
                                break;
                            default:
                        }
                        $pivot->save();
                    }
                }
            });
        });
    }

    protected function extendUserController()
    {
        Event::listen('backend.form.extendFields', function($widget)
        {

            // Only for the User controller
            if (!$widget->getController() instanceof \RainLab\User\Controllers\Users)
            {
                return;
            }

            // Only for the User model
            if (!$widget->model instanceof \RainLab\User\Models\User)
            {
                return;
            }


            $widget->addTabFields([
                'userpermissioneditor' => [
                    'tab' => 'Permissions',
                    'label'   => 'jbonnydev.userpermissions::lang.permissions.menu_label',
                    'type'    => 'userpermissioneditor',
                    'mode' => 'radio',
                    'context' => ['create','preview','update'],
                ]
            ]);
        });
    }

    protected function extendUserGroupController()
    {
        Event::listen('backend.form.extendFields', function($widget)
        {
            // Only for the UserGroup controller
            if (!$widget->getController() instanceof \RainLab\User\Controllers\UserGroups)
            {
                return;
            }

            // Only for the UserGroup model
            if (!$widget->model instanceof \RainLab\User\Models\UserGroup)
            {
                return;
            }


            $widget->addTabFields([
                'userpermissioneditor' => [
                    'tab' => 'Permissions',
                    'label'   => 'jbonnydev.userpermissions::lang.permissions.menu_label',
                    'type'    => 'userpermissioneditor',
                    'mode' => 'checkbox',
                    'context' => ['create','preview','update'],
                ]
            ]);
        });
    }

    public function registerFormWidgets()
    {
        return [
            'JBonnyDev\UserPermissions\FormWidgets\UserPermissionEditor' => 'userpermissioneditor',
        ];
    }
}
