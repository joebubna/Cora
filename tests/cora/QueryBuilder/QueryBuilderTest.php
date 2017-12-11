<?php
namespace Tests\Cora;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{   
    /**
    *  Check that can select single field
    *
    *  @test
    */
    public function canSelectSingle()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb->select('name')
           ->from('users');

        $this->assertEquals(['name'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
    }
    

    /**
    *  Check that adding multiple SELECT fields works properly
    *
    *  @test
    */
    public function canSelectMultipleWithFunctionCalls()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb->select('name')
           ->select('email')
           ->from('users');

        $this->assertEquals(['name','email'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
    }


    /**
    *  Check that adding multiple SELECT fields works properly
    *
    *  @test
    */
    public function canSelectMultipleWithArray()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb ->select(['name', 'email'])
            ->from('users');

        $this->assertEquals(['name','email'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
    }


    /**
    *  Check Distinct
    *
    *  @test
    */
    public function canSelectDistinct()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb ->select('name')
            ->distinct()
            ->from('users');

        $this->assertEquals(['name'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        $this->assertEquals(true, $qb->distinct);
    }


    /**
    *  Check JOIN simple
    *
    *  @test
    */
    public function canJoin()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [['users.role', '=', ':roles.role_id']]);

        $this->assertEquals(['name'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        $this->assertEquals([
            ['roles', [
                ['users.role', '=', ':roles.role_id']
            ], 'INNER']
        ], $qb->joins);
    }


    /**
    *  Check JOIN modified
    *
    *  @test
    */
    public function canJoinModified()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [['users.role', '=', ':roles.role_id']], 'OUTER');

        $this->assertEquals(['name'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        $this->assertEquals([
            ['roles', [
                ['users.role', '=', ':roles.role_id']
            ], 'OUTER']
        ], $qb->joins);
    }


    /**
    *  Check JOIN complex
    *
    *  @test
    */
    public function canJoinComplex()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [
                ['users.role', '=', ':roles.role_id'],
                ['users.status', '=', 'active']
            ], 'OUTER');

        $this->assertEquals(['name'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        $this->assertEquals([
            ['roles', [
                ['users.role', '=', ':roles.role_id'],
                ['users.status', '=', 'active']
            ], 'OUTER']
        ], $qb->joins);
    }


    /**
    *  Check JOIN multiple
    *
    *  @test
    */
    public function canJoinMultiple()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [
                ['users.role', '=', ':roles.role_id'],
                ['users.status', '=', 'active']
            ], 'OUTER')
            ->join('users_meta', [['users.id', '=', ':users_meta.user_id']]);
            
        $this->assertEquals(['name'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        $this->assertEquals([
            [
                'roles', 
                [
                    ['users.role', '=', ':roles.role_id'],
                    ['users.status', '=', 'active']
                ], 
                'OUTER'
            ],
            [
                'users_meta',
                [
                    ['users.id', '=', ':users_meta.user_id']
                ], 
                'INNER'
            ]
        ], $qb->joins);
        
    }


    /**
    *  Check that adding multiple WHERE fields works properly
    *  
    *  @test
    */
    public function canWhereMultipleWithFunctionCalls()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb->select(['name', 'email'])
           ->from('users')
           ->where('status', 'active')
           ->where('money', ':debt', '>');

        $this->assertEquals(['name', 'email'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        
        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[0]);
        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[1]);
        $this->assertEquals('status', $qb->wheres[0]->group[0]->leftExpr);
        $this->assertInstanceOf(\Cora\Data\DbField::class, $qb->wheres[1]->group[0]->rightExpr);
    }


    /**
    *  Check that embedding WHERE fields works properly
    *
    *  @test
    */
    public function canWhereEmbedded()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb->select(['name', 'email'])
           ->from('users')
           ->where('status', 'active')
           ->where(function($qb) {
               $qb->where('name', '%dolly%', 'LIKE')
                  ->orWhere('type', 'Admin');
           });

        $this->assertEquals(['name', 'email'], $qb->selects);
        $this->assertEquals(['users'], $qb->tables);
        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[0]);
        $this->assertInstanceOf(\Cora\Data\QueryBuilder::class, $qb->wheres[1]->group[0]);
        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[1]->group[0]->wheres[0]);
        $this->assertEquals('name', $qb->wheres[1]->group[0]->wheres[0]->group[0]->leftExpr);
        $this->assertEquals('Admin', $qb->wheres[1]->group[0]->wheres[1]->group[0]->rightExpr);
    }


    /**
    *  Check that IN works properly
    *  
    *  @test
    */
    public function canIn()
    {
        $qb = new \Cora\Data\QueryBuilder();
        $qb->select(['name', 'email'])
           ->from('users')
           ->where('status', 'active')
           ->in('type', ['Admin', 'Manager'])
           ->in('country', function($qb) {
               $qb->select('country')
                  ->from('suppliers');
           });

        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[0]);
        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[1]);
        $this->assertInstanceOf(\Cora\Data\DbExprGroup::class, $qb->wheres[2]);

        $this->assertEquals('status', $qb->wheres[0]->group[0]->leftExpr);
        $this->assertInstanceOf(array(), $qb->wheres[1]->group[0]->rightExpr);
    }


    // $qb->select(new DbFunction('SUM', 'money'))
    // $qb->select(new DbFunction('SUM', new DbFunction('CONCAT', 'name')))
    // DbFunction takes a name and value (which could be another function). Has toString method.

    // ALIASES
    // ->setColumns(['userId' => 'user_id', 'username' => 'name', 'email' => 'email']);
    // SELECT user.user_id AS 'userId'

    // $qb = new \Cora\Data\QueryBuilder();
    // $qb->select(['name', 'email'])
    //     ->from('users')
    //     ->where('status', 'active')
    //     ->where('money', ':debt', '>')
    //     ->where(function($qb) {
    //         $qb->where('blah')
    //            ->orWhere('blah');  
    //     });
}