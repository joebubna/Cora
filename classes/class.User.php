<?php 
/**
* 
*/
class User extends MyModel {
    
    //public $model_table = 'notes_tasks';
    //public $model_connection = 'MySQL';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar',
            'size' => 155
        ],
        'email' => [
            'type' => 'varchar',
            'index' => true
        ],
        'type'  => [
            'type' => 'enum',
            'enum' => "'User', 'Moderator', 'Admin'",
            'defaultValue' => 'User'
        ],
        'created' => [
            'type' => 'datetime'  
        ],
        'location' => [
            'model' => 'location'
        ],
        'job' => [
            'model' => 'job',
            'usesRefTable' => true,
            'relTable' => 'ref_users_jobs'
        ],
        'articles' => [
            'models' => 'Article',
            'via'    => 'owner'
        ],
        'guides' => [
            'models' => 'guide',
            'passive' => true
        ],
        'notes' => [
            'models' => 'task\\note',
            'via'    => 'owner'
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