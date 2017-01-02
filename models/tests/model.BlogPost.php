<?php 
namespace Models\Tests;
/**
* 
*/
class BlogPost extends \Cora\App\Model {
    
    public $model_connection = 'MySQL2';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'owner' => [
            'model' => 'Tests\\User'
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