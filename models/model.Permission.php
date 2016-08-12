<?php 
/**
* 
*/
class Permission extends MyModel {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ]
    ];
    
    public function __construct($name = null)
    {
        $this->name = $name;
    }

}