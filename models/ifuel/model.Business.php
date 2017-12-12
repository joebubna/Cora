<?php 
namespace Models\Ifuel;
/**
* 
*/
class Business extends \Cora\App\Model {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'type' => [
            'type' => 'varchar'
        ],
        'status' => [
            'type' => 'varchar'
        ],
        'parent' => [
            'model' => 'Ifuel\\Business'
        ]
    ];
    
    public function __construct($name = null)
    {
        $this->name = $name;
    }

}