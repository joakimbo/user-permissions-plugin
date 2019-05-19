<?php namespace JBonnyDev\UserPermissions\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateTables extends Migration
{
    public function up()
    {
        Schema::create('jbonnydev_userpermissions_permissions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('jbonnydev_userpermissions_user_permission', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('user_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->tinyInteger('permission_state');
            $table->primary(['user_id', 'permission_id'], 'user_permission_id');
            $table->timestamps();
        });

        Schema::create('jbonnydev_userpermissions_group_permission', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('group_id')->unsigned();
            $table->integer('permission_id')->unsigned();
            $table->tinyInteger('permission_state');
            $table->primary(['group_id', 'permission_id'], 'group_permission_id');
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('jbonnydev_userpermissions_group_permission');
        Schema::dropIfExists('jbonnydev_userpermissions_user_permission');
        Schema::dropIfExists('jbonnydev_userpermissions_permissions');
    }
}
