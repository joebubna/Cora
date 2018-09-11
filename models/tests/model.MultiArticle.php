<?php 
namespace Models\Tests;
/**
* 
*/
class MultiArticle extends \Cora\App\Model {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'authors' => [
            'models' => 'Tests\\User',
            'relName' => 'authorPaper'
        ]
    ];
    
    public function __construct($name = null)
    {
        $this->name = $name;
    }
}