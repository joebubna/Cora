<?php 
/**
* 
*/
class Note extends MyModel {
    
    //public $model_connection = 'MySQL';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'note' => [
            'type' => 'text'
        ],
        'owner' => [
            'model' => 'user'
        ]
    ];
    
    public function __construct($note = null)
    {
        $this->note = $note;
    }

    public function foo() {
        return 'This is a note.';
    }

}