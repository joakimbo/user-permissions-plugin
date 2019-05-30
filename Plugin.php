<?php namespace JBonnyDev\UserPermissions;

use Exception;
use Event;
use Backend;
use JBonnyDev\UserPermissions\Models\Permission as PermissionModel;
use Rainlab\User\Models\User as UserModel;
use Rainlab\User\Models\UserGroup as UserGroupModel;
use October\Rain\Exception\ApplicationException;
use Illuminate\Support\Facades\DB as Db;

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
        function getAllowedPermissions($model) {
            if(!$model->is_activated) {
                return [];
            }

            $firstQuery = Db::table('users')
                ->join('jbonnydev_userpermissions_user_permission', function($join) {
                    $join->on('users.id', '=', 'jbonnydev_userpermissions_user_permission.user_id')
                        ->where('jbonnydev_userpermissions_user_permission.permission_state', '=', 2);
                })
                ->join('users_groups', 'users.id', '=', 'users_groups.user_id')
                ->join('user_groups', 'users_groups.user_group_id', '=', 'user_groups.id')
                ->join('jbonnydev_userpermissions_group_permission', function ($join) {
                    $join->on('user_groups.id', '=', 'jbonnydev_userpermissions_group_permission.group_id')
                    ->on(
                        'jbonnydev_userpermissions_group_permission.permission_id',
                        '=',
                        'jbonnydev_userpermissions_user_permission.permission_id'
                    )
                    ->where('jbonnydev_userpermissions_group_permission.permission_state', '=', 1);
                })
                ->join('jbonnydev_userpermissions_permissions',
                    'jbonnydev_userpermissions_group_permission.permission_id',
                    '=',
                    'jbonnydev_userpermissions_permissions.id'
                )
                ->where('users.id', '=', $model['id'])
                ->select(
                    'jbonnydev_userpermissions_permissions.id',
                    'jbonnydev_userpermissions_permissions.code',
                    'jbonnydev_userpermissions_permissions.name'
                );

            $permissionsResult = Db::table('users')
                ->join('jbonnydev_userpermissions_user_permission', function($join) {
                    $join->on('users.id', '=', 'jbonnydev_userpermissions_user_permission.user_id')
                        ->where('jbonnydev_userpermissions_user_permission.permission_state', '=', 1);
                })
                ->join('jbonnydev_userpermissions_permissions',
                    'jbonnydev_userpermissions_user_permission.permission_id',
                    '=',
                    'jbonnydev_userpermissions_permissions.id'
                )
                ->where('users.id', '=', $model['id'])
                ->select(
                    'jbonnydev_userpermissions_permissions.id',
                    'jbonnydev_userpermissions_permissions.code',
                    'jbonnydev_userpermissions_permissions.name'
                )
                ->union($firstQuery)
                ->get();

            if(!$permissionsResult) {
                $permissionsResult = [];
            } else {
                $permissionsResult = $permissionsResult->toArray();
            }
            return $permissionsResult;
        }

        function hasUserPermission($permissionInput, $column, $allowedPermissionsCollection) {
            if (is_array($allowedPermissionsCollection) && count($allowedPermissionsCollection) > 0) {
                foreach ($allowedPermissionsCollection as $permission) {
                    if ($permission->{$column} == $permissionInput) {
                        return true;
                    }
                }
            }
            return false;
        }

        function normalizePermissionInput($permissions) {
            if (!is_array($permissions)) {
                $permissions = [$permissions];
            }
            $permissions = array_filter($permissions, function ($element) {
                if (is_string($element) || is_int($element)) {
                    return true;
                }
                return false;
            });
            return $permissions;
        }

        UserModel::extend(function($model)
        {
            $model->belongsToMany['user_permissions'] = [
                'JBonnyDev\UserPermissions\Models\Permission',
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
                        $model->user_permissions()->attach($permission->id, ['permission_state' => 2]);
                    }
                }

            });

            $model->addDynamicMethod('hasUserPermission', function($permissionsInput, $match = 'all') use ($model)
            {
                if (!is_string($match) || $match != 'all' && $match != 'one') {
                    throw new ApplicationException('second parameter of hasUserPermission() must be of type string with a value of "all" or "one"!');
                }

                $permissionsInput = normalizePermissionInput($permissionsInput);
                if (is_array($permissionsInput) && count($permissionsInput) > 0) {
                    $result = [];
                    $allowedPermissions = getAllowedPermissions($model);
                    foreach ($permissionsInput as $permissionInput) {
                        if (is_string($permissionInput)) {
                            $result[] = hasUserPermission($permissionInput, 'code', $allowedPermissions);
                        } elseif(is_int($permissionInput)) {
                            $result[] = hasUserPermission($permissionInput, 'id', $allowedPermissions);
                        }
                    }
                    if ($match == 'all') {
                        return !in_array(false, $result);
                    } elseif($match == 'one') {
                        return in_array(true, $result);
                    }
                }
                return false;
            });
        });
    }

    protected function extendUserGroupModel()
    {
        UserGroupModel::extend(function($model)
        {
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
                        $model->user_permissions()->attach($permission->id);
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
                'user_permissions' => [
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
                'user_permissions' => [
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
