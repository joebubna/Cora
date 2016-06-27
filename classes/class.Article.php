<?php 
/**
* 
*/
class Article extends MyModel {
    
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
            'model' => 'location'
        ]
    ];
    
    public static function exists($title = false) {
        
        // Pretend we're checking if an article with this title already exists.
        return $title == 'test' ? true : false;
    }

}