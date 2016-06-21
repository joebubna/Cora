<?php 
/**
* 
*/
class User extends MyModel {
    
    public $model_connection = 'mysql';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'email' => [
            'type' => 'varchar'
        ],
        'type'  => [
            'type' => 'varchar'
        ],
        'location' => [
            'model' => 'locations'
        ]
    ];
    
    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    public function getName() {
        return $this->name;
    }

}