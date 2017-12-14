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
        'parent' => [
            'model' => 'Ifuel\\Business'
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'type' => [
            'type' => 'varchar'
        ],
        'status' => [
            'type' => 'varchar'
        ]
    ];
    
    public function __construct($name = null)
    {
        $this->name = $name;
    }

}