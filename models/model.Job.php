<?php 
/**
* 
*/
class Job extends MyModel {
    
    //public $model_connection = 'MySQL';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'title' => [
            'type' => 'varchar'
        ],
        'description' => [
            'type' => 'varchar'
        ]
    ];
    
    public function __construct($title = null, $description = null)
    {
        $this->title = $title;
        $this->description = $description;
    }

}