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
            'models' => 'Tests\\Users\\Comment',
            'via' => 'madeBy'
        ],
        'articles' => [ // Stored in 2nd database
            'models' => 'Tests\\Article'
        ],
        'blogposts' => [ // Stored is 2nd database
            'models' => 'Tests\\BlogPost',
            'via' => 'owner'
        ],
        'father' => [
            'model' => 'Tests\\User'
        ],
        'mother' => [
            'model' => 'Tests\\User',
            'usesRefTable' => true
        ],
        'mother2' => [ // Should return the same results as "mother" attribute.
            'model' => 'Tests\\User',
            'usesRefTable' => true,
            'relTable' => 'ref_tests_users__mother__tests_users'
        ],
        'friends' => [
            'models' => 'Tests\\User'
        ],
        'friends2' => [ // Should return the same results as the "friends" attribute.
            'models' => 'Tests\\User',
            'relTable' => 'ref_tests_users__friends__tests_users'
        ],
        'dates' => [
            'models' => 'Tests\\Date',
            'via' => 'owner'
        ]
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
