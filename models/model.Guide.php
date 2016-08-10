<?php 
/**
* 
*/
class Guide extends MyModel {
    
    public $model_connection = 'MySQL2';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'title' => [
            'type' => 'varchar'
        ],
        'authors' => [
            'models' => 'user'
        ]
    ];
    
    public function __construct($title = null)
    {
        $this->title = $title;
    }

}