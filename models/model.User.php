<?php 
/**
* 
*/
class User extends MyModel {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'username' => [
            'type' => 'varchar'
        ],
        'email' => [
            'type' => 'varchar',
            'index' => true
        ],
        'password' => [
            'type' => 'varchar'  
        ],
        'token' => [
            'type' => 'varchar'  
        ],
        'createdDate' => [
            'type' => 'datetime'  
        ],
        'primaryRole' => [
            'type' => 'varchar',
            'size' => 55,
            'defaultValue' => 'User'
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
        'articles' => [
            'models' => 'Article',
            'via' => 'owner'
        ]
    ];
    
    public function __construct($name = null, $email = null, $password = null, $type = null)
    {
        $this->username = $name;
        $this->email = $email;
        $this->password = $password;
        $this->type = $type;
    }
    
    public function beforeCreate() {
        $this->createdDate = new \DateTime();
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