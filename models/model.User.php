<?php 
/**
* 
*/
class User extends AppModel {
    
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
    
    public function __construct($email = null, $password = null, $type = null)
    {
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