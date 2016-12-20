<?php 
namespace Models\Tests\Users;
/**
* 
*/
class Comment extends \Models\Tests\Comment {
    
    public function __construct($madeBy = null, $text = null)
    {
        $this->model_attributes['madeBy'] = [
            'model' => 'Tests\User'
        ];
        
        $this->madeBy = $madeBy;
        $this->text = $text;
    }
}