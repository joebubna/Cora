<?php 
namespace Models\Tests;
/**
* 
*/
abstract class Comment extends \Cora\App\Model {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'text' => [
            'type' => 'text'
        ]
    ];
    
    public function __construct($text = null)
    {
        parent::__construct();
        $this->text = $text;
    }
}