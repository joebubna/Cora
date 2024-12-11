<?php 
namespace Models\Tests\Users;
/**
* 
*/
class CommentUser extends \Cora\App\Model {
    
    public $model_table = 'tests_users_comments';
    public $model_connection = 'MySQLTest';
    public $model_extends = 'madeBy';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'text' => [
            'type' => 'text'
        ],
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