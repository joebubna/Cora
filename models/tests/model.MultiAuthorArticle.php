<?php 
namespace Models\Tests;
/**
* 
*/
class MultiAuthorArticle extends \Cora\App\Model {
    
    public $model_connection = 'MySQL2';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'text' => [
            'type' => 'text'
        ],
        'authors' => [
            'models' => 'Tests\\User',
            //'relTable' => 'ref_tests_users__multi_author_articles__tests_multi_author_article',
            'passive' => true
        ]
    ];
    
    public function __construct($text = null)
    {
        $this->text = $text;
    }
}