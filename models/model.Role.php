<?php 
namespace Models;
/**
* 
*/
class Role extends \Cora\App\Model {
    
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