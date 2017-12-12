<?php 
namespace Models\Ifuel;
/**
* 
*/
class Practice extends \Cora\App\Model {
    
    public $model_extends = 'business';
    public $model_attributes = [ 
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'business' => [
            'model' => 'Ifuel\\Business'
        ],
        'practiceType' => [
            'type' => 'varchar'
        ],
        'physicians' => [
            'type' => 'varchar'
        ],
        'lastActivityTime' => [
            'type' => 'datetime'
        ]
    ];
    
    public function __construct()
    {   parent::__construct();
        
    }

    public function beforeCreate() 
    {
        if (!$this->lastActivityTime) {
            $this->lastActivityTime = new \DateTime();
        }
    }

}