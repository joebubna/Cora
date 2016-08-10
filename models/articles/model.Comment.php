<?php 
namespace Articles;
/**
* 
*/
class Comment extends \Comment {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'madeBy' => [
            'model' => 'user'
        ],
        'timestamp' => [
            'type' => 'datetime'
        ],
        'text' => [
            'type' => 'text'
        ],
        'owner' => [
            'model' => 'Article'
        ]
    ];
    
    public function __construct($article, $user, $text) 
    {
        $this->owner = $article;
        $this->madeBy = $user;
        $this->text = $text;
    }

    public function foo()
    {
        return 'This is an article comment.<br>';
    }

}