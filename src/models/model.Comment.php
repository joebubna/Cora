<?php 
namespace Models;
/**
* 
*/
class Comment extends \Cora\App\Model {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'madeBy' => [
            'model' => 'User'
        ],
        'timestamp' => [
            'type' => 'datetime'
        ],
        'text' => [
            'type' => 'text'
        ],
        'status' => [
            'type' => 'varchar',
            'defaultValue' => 'Active'
        ]
    ];
    
    public function __construct($madeBy = null, $text = null)
    {
        $this->madeBy = $madeBy;
        $this->text = $text;
    }
}