<?php 
namespace Models\Tests\Users;
/**
* 
*/
class Comment extends \Models\Tests\Comment {
    
    public $model_attributes_add = [
        'madeBy' => [
            'model' => 'Tests\User'
        ]
    ];
    
    public function __construct($madeBy = null, $text = null)
    {
        parent::__construct();
        
        $this->madeBy = $madeBy;
        $this->text = $text;
    }
}