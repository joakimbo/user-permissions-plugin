<?php namespace JBonnyDev\UserPermissions\FormWidgets;

use Backend\Classes\FormWidgetBase;
use BackendAuth;
use JBonnyDev\UserPermissions\Models\Permission as PermissionModel;

class UserPermissionEditor extends FormWidgetBase
{
    protected $user;
    public $mode;
    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'mode'
        ]);
        $this->user = BackendAuth::getUser();
    }
    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('userpermissioneditor');
    }
    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        if ($this->formField->disabled) {
            $this->previewMode = true;
        }
        $permissionsData = $this->model->user_permissions()->lists('permission_state', 'id');
        if (!is_array($permissionsData)) {
            $permissionsData = [];
        }
        $this->vars['checkboxMode'] = $this->getControlMode() === 'checkbox';
        $this->vars['permissions'] = $this->getFilteredPermissions();
        $this->vars['baseFieldName'] = $this->getFieldName();
        $this->vars['permissionsData'] = $permissionsData;
        $this->vars['field'] = $this->formField;
    }
    /**
     * @inheritDoc
     */
    public function getSaveValue($value)
    {
        $result = [];
        if(isset($value)) {
            foreach($value as $permissionId => $permissionState) {
                if($permissionState != 0) {
                    $result[$permissionId] = ['permission_state' => $permissionState];
                }
            }
        }
        return $result;
    }
    /**
     * @inheritDoc
     */
    protected function loadAssets()
    {
        $this->addCss('css/permissioneditor.css', 'core');
        $this->addJs('js/permissioneditor.js', 'core');
    }
    protected function getControlMode()
    {
        return strlen($this->mode) ? $this->mode : 'radio';
    }
    /**
     * Returns the available permissions; removing those that the logged-in user does not have access to
     *
     * @return array The permissions that the logged-in user does have access to
     */
    protected function getFilteredPermissions()
    {
        $permissions = PermissionModel::all();
        return $permissions;
    }
}
