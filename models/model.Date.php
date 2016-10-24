<?php 
namespace Models;
/**
* 
*/
class Date extends \Cora\App\Model {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'desc' => [
            'type' => 'varchar'
        ],
        'date' => [
            'type' => 'datetime'
        ]
    ];
    
    public function __construct($name = null, $desc = null, $date = null)
    {
        $this->name = $name;
        $this->desc = $desc;
        $this->date = $date;
    }

}