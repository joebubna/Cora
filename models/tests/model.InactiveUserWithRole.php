<?php
namespace Models\Tests;
/**
*
*/
class InactiveUserWithRole extends \Models\User {

    public $model_table = 'users';
    public $model_attributes_add = [
        'status' => [       // Active, Deactivated, Banned
            'type' => 'varchar',
            'defaultValue' => 'Inactive'
        ]
    ];


    public static function model_loadMap() 
    {
        return new \Cora\Adm\LoadMap([], [
          'primaryRole' => new \Cora\Adm\LoadMap()
        ]);
    }

    public static function model_constraints($query) 
    {
        $query->join('roles', [['users.primaryRole', '=', 'roles.id']])
              ->where('status', 'Inactive');
        return $query;
    }

}