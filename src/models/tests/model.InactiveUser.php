<?php
namespace Models\Tests;
/**
*
*/
class InactiveUser extends \Models\User {

    public $model_table = 'users';
    public $model_attributes_add = [
        'status' => [       // Active, Deactivated, Banned
            'type' => 'varchar',
            'defaultValue' => 'Inactive'
        ]
    ];


    public static function model_constraints($query) 
    {
        $query->where('status', 'Inactive');
        return $query;
    }

}
