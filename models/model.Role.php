<?php 
/**
* 
*/
class Role extends MyModel {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'permissions' => [
            'models' => 'Permission'
        ],
        'group' => [
            'model' => 'Group'
        ]
    ];
    
    public function __construct($name = null)
    {
        $this->name = $name;
    }

}