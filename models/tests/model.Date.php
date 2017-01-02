<?php 
namespace Models\Tests;
/**
* 
*/
class Date extends \Cora\App\Model {

    public $model_attributes = [
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'owner' => [
            'model' => 'Tests\User'
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'timestamp' => [
            'type' => 'datetime'
        ]
    ];

    public function __construct($name = null, $date = null)
    {
        $this->name = $name;
        $this->timestamp = $date;
    }

}