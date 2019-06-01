# UserPermissions Plugin

Front-end user permissions management.

## Requirements

This plugin requires the [RainLab.User](https://github.com/rainlab/user-plugin/) Plugin.

## Creating Permissions

In the backend, navigate to RainLab "Users" menu, on the left side there should  
be a open lock icon with the name "Permissions". Click this and it will take you  
to the list of permission.  
- Click "New Permission" to get to a form where you can enter information about a new  
permission you would like to create (dont forget to save).  
- Click on a permission in the list to manage existing permissions.

## Managing User Permissions

In the backend, navigate to RainLab "Users" menu, either create a new user by  
clicking "New User" and navigate to the "Permissions" tab of the newly opened form.  
Here you can choose between "ALLOW", "INHERIT" or "DENY" for all existing permissions.  
- "ALLOW" will grant the user the permission, this takes precedence over group permissions.  
- "INHERIT" will grant the user the permission only if they also belong to a group which  
also has the permission set to allow (if they are checked).  
- "DENY" will NOT grant the user the permission, this takes precedence over group  
permissions meaning that even if the user belongs to a group with the permission  
allowed the user will not have the permission.  

The same tab is available for all existing users, simply click the user you want  
to manage in the user list and navigate to the "Permissions" tab.

## Managing Group Permissions

In the backend, navigate to RainLab "Users" menu, either create a new group by  
clicking "User Groups" and then on "New Group" or click the group you want to edit.  
Navigate to the "Permissions" tab and click (check) all the permissions you want this group to have.

## Using UserPermissions in your own development

Available UserPermissions functions:

    /**
     * Check if user has supplied permissions
     *
     * @param mixed $permission Single string, single integer or Array structure with strings and/or integers
     * @param string $match How to match the permission(s), "all" or "one", defaults to "all"
     *
     * @return boolean Returns if user has supplied permission(s)
     */
    function hasUserPermission($permission, $match = 'all') {
        <...>
    }

Since every user model is extended with the same function it is available in both twig and backend php i.e.

**For Twig**

    {% if user.hasUserPermission([1, 2, "can-eat-cake"]) %}
        <p>This user has all above permissions</p>
    {% else %}
        <p>This user does not have permission</p>
    {% endif %}

    {% if user.hasUserPermission([1, 2, "can-eat-cake"], 'one') %}
        <p>This user has one of the above permissions</p>
    {% else %}
        <p>This user does not have permission</p>
    {% endif %}

**For Backend**

    if($user->hasUserPermission([1, 2, "can-eat-cake"])) {
        // This user has all above permissions
    } else {
        // This user does not have permission
    }

    if($user->hasUserPermission([1, 2, "can-eat-cake"], 'one')) {
        // This user has one of the above permissions
    } else {
        // This user does not have permission
    }

