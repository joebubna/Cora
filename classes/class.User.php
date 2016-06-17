<?php 
/**
* 
*/
class User extends MyModel {
    
    public $model_connection = 'mysql';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'email' => [
            'type' => 'varchar'
        ],
        'type'  => [
            'type' => 'varchar'
        ],
        'location' => [
            'model' => 'locations'
        ]
    ];
    
    public function test() {
        return 'Bob';
    }

}