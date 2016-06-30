<?php 
namespace Task;
/**
* 
*/
class Note extends \Note {
    
    //public $model_table = 'notes_tasks';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'title' => [
            'type' => 'varchar'
        ],
        'note' => [
            'type' => 'text'
        ],
        'owner' => [
            'model' => 'user'
        ]
    ];
    

    public function foo()
    {
        return 'This is a task note.<br>';
    }

}