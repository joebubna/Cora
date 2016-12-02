<?php
namespace Models\Tests;
/**
*
*/
class User extends \Cora\App\Model {

    public $model_attributes = [
        'id' => [
            'type'          => 'int',
            'primaryKey'    => true
        ],
        'name' => [
            'type' => 'varchar'
        ],
        'type' => [
            'type' => 'varchar'
        ],
        'comments' => [
            'models' => 'Tests\Users\Comment',
            'via' => 'madeBy'
        ],
        'parent' => [
            'model' => 'User'
        ],
        // 'roleName' => [
        //     'from' => 'roles',
        //     'select' => 'name',
        //     'where' => ['primaryRole', '=', 'id']
        // ]
    ];

    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

}
