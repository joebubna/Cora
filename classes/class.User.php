<?php 
/**
* 
*/
class User extends MyModel {
    
    //public $model_connection = 'MySQL';
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
            'model' => 'location'
        ],
        'job' => [
            'model' => 'job',
            'usesRefTable' => true
        ],
        'articles' => [
            'models' => 'article',
            'via'    => 'owner'
        ],
        'guides' => [
            'models' => 'guide'
        ]
    ];
    
    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    public function getName() {
        return $this->name;
    }

}