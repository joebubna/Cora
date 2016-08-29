<?php 
/**
* 
*/
class Role extends AppModel {
    
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