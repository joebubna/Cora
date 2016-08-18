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
        ],
        'allow' => [
            'type' => 'int',
            'defaultValue' => 1
        ],
        
        // The group restriction this permission applies to (if any)
        // If none specified, then permission applies to non-group areas.
        'group' => [
            'model' => 'Group'
        ]
    ];
    
    public function __construct($name = null)
    {
        $this->name = $name;
    }

}