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
            'type' => 'varchar',
            'defaultValue' => 'Standard'
        ],
        'birthday' => [
            'type' => 'datetime',
            'defaultValue' => '1982-05-02'
        ],
        'lastModified' => [
            'type' => 'datetime',
            'field' => 'modified_time'
        ],
        'comments' => [
            'models' => 'Tests\\Users\\Comment',
            'via' => 'madeBy'
        ],
        'articles' => [ // Stored in 2nd database
            'models' => 'Tests\\Article',
        ],
        'multiAuthorArticles' => [ // Stored in 2nd database
            'models' => 'Tests\\MultiAuthorArticle'
        ],
        'blogposts' => [ // Stored is 2nd database
            'models' => 'Tests\\BlogPost',
            'via' => 'owner'
        ],
        'grandpa' => [
            'model' => 'Tests\\User',
            'field' => 'grandfather'
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
        'contacts' => [ // This is for testing custom relation table field names.
            'models' => 'Tests\\User',
            'relTable' => 'ref_users_contacts',
            'relThis' => 'user_id',
            'relThat' => 'contact_id'
        ],
        'dates' => [
            'models' => 'Tests\\Date',
            'via' => 'owner'
        ],
        'writings' => [
            'models' => 'Tests\\MultiArticle',
            'relName' => 'authorPaper'
        ],
        'sameType' => [
            'models' => 'Tests\\User',
            'using' => '_similarUsers'
        ],
        'multiAbstract' => [
            'models' => 'Role',
            'using' => '_customRoles'
        ],
        'singleAbstract' => [
            'model' => 'Role',
            'using' => '_customRoles'
        ]
    ];

    public function __construct($name = null, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    protected function _similarUsers($query) {
        $query->where('type', $this->type)
              ->where('id', $this->id, '<>');
        return $query;
    }

    protected function _customRoles($query) {
        $query->where('id', 3, '<');
        return $query;
    }

}
