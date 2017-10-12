<?php
namespace Models;
/**
*
*/
class User extends \Cora\App\Model {

    public $model_attributes = [
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'email' => [
            'type' => 'varchar',
            'index' => true
        ],
        'password' => [
            'type' => 'varchar'
        ],
        'firstName' => [
            'type' => 'varchar'
        ],
        'lastName' => [
            'type' => 'varchar'
        ],
        'status' => [       // Active, Deactivated, Banned
            'type' => 'varchar',
            'defaultValue' => 'Active'
        ],
        'token' => [
            'type' => 'varchar'
        ],
        'resetToken' => [
            'type' => 'varchar'
        ],
        'resetTokenExpire' => [
            'type' => 'datetime'
        ],
        'createdDate' => [
            'type' => 'datetime'
        ],
        'primaryRole' => [
            'model' => 'Role'
        ],
        'roles' => [
            'models' => 'Role'
        ],
        'permissions' => [
            'models' => 'Permission'
        ],
        'groups' => [
            'models' => 'Group'
        ],
        'comments' => [
            'models' => 'Comment',
            'via' => 'madeBy'
        ],
        'parent' => [
            'model' => 'User'
        ],
        'alike' => [
            'models' => 'User',
            'using' => 'relAlike'
        ]
        // 'roleName' => [
        //     'from' => 'roles',
        //     'select' => 'name',
        //     'where' => ['primaryRole', '=', 'id']
        // ]
    ];

    public function __construct($email = null, $password = null, $type = null)
    {
        parent::__construct();
        $this->email = $email;
        $this->password = $password;
        $this->primaryRole = $type;
    }

    public function beforeGet($name) {
        if ($name == 'name') {
            $this->name = $this->firstName.' '.$this->lastName;
        }
    }

    public function beforeCreate() {
        $this->createdDate = new \DateTime();
    }

    public function onLoad() 
    {
        $this->data->calcName = $this->firstName.'-'.$this->lastName;
    }

//    public function afterGet($prop, $value)
//    {
//        if ($prop == 'roles') {
//            foreach ($values as $role) {
//                foreach ($role->)
//            }
//        }
//    }

}
