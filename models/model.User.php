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
        'type'  => [
            'type' => 'varchar',
            'side' => 55,
            'defaultValue' => 'User'
        ],
        'createdDate' => [
            'type' => 'datetime'  
        ],
        'articles' => [
            'models' => 'Article',
            'via'    => 'owner'
        ]
    ];
    
    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    public function beforeCreate() {
        $this->createdDate = new \DateTime();
    }

}