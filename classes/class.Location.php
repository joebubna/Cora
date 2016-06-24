<?php

/**
 *
 */
class Location extends MyModel {
    
    public $model_attributes = [
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type'          => 'varchar'
        ],
        'city' => [
            'type'          => 'varchar'
        ]
    ];
    
    public function __construct($name = null, $city = null)
    {
        
    }
}