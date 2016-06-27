<?php 
/**
* 
*/
class Guide extends MyModel {
    
    //public $model_connection = 'MySQL';
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

}