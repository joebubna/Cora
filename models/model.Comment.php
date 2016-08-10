<?php 
/**
* 
*/
class Comment extends MyModel {
    
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'madeBy' => [
            'model' => 'user'
        ],
        'timestamp' => [
            'type' => 'datetime'
        ],
        'text' => [
            'type' => 'text'
        ]
    ];
    
    public function __construct($text = null)
    {
        $this->text = $text;
    }

    public function foo() {
        return 'This is a generic comment.';
    }

}