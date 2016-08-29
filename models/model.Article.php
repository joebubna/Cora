<?php 
/**
* 
*/
class Article extends AppModel {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'title' => [
            'type' => 'varchar'
        ],
        'text' => [
            'type' => 'varchar'
        ],
        'owner' => [
            'model' => 'User'
        ],
        'comments' => [
            'models' => 'Comment'
        ]
    ];
    
    public function __construct($title = null)
    {
        $this->title = $title;
    }
    
    public static function exists($title = false) {
        
        // Pretend we're checking if an article with this title already exists.
        return $title == 'test' ? true : false;
    }

}