<?php 
namespace Models\Tests;
/**
* 
*/
class Article extends \Cora\App\Model {
    
    public $model_connection = 'MySQL2';
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
        $this->text = $text;
    }
}