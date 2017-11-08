<?php
namespace Tests\Cora\QueryBuilder;

class MySQLTest extends \PHPUnit\Framework\TestCase
{   
    /**
    *  Check that can select single field
    *
    *  @test
    */
    public function canSelectSingle()
    {
        $qb = new \Cora\Db_MySQL();
        $qb->select('name')
           ->from('users');

        //$query = $qb->getQuery();

        $this->assertEquals('SELECT name FROM users', (string) $qb);
    }
    

    /**
    *  Check that adding multiple SELECT fields works properly
    *
    *  @test
    */
    public function canSelectMultipleWithFunctionCalls()
    {
        $qb = new \Cora\Db_MySQL();
        $qb->select('name')
           ->select('email')
           ->from('users');

        $this->assertEquals('SELECT name, email FROM users', (string) $qb);
    }


    /**
    *  Check that adding multiple SELECT fields works properly
    *
    *  @test
    */
    public function canSelectMultipleWithArray()
    {
        $qb = new \Cora\Db_MySQL();
        $qb ->select(['name', 'email'])
            ->from('users');

        $this->assertEquals('SELECT name, email FROM users', (string) $qb);
    }


    /**
    *  Check Distinct
    *
    *  @test
    */
    public function canSelectDistinct()
    {
        $qb = new \Cora\Db_MySQL();
        $qb ->select('name')
            ->distinct()
            ->from('users');

        $this->assertEquals('SELECT DISTINCT name FROM users', (string) $qb);
    }


    /**
    *  Check JOIN simple
    *
    *  @test
    */
    public function canJoin()
    {
        $qb = new \Cora\Db_MySQL();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [['users.role', '=', ':roles.role_id']]);

        $this->assertEquals('SELECT name FROM users JOIN roles ON (users.role = roles.role_id)', (string) $qb);
    }


    /**
    *  Check JOIN modified
    *
    *  @test
    */
    public function canJoinModified()
    {
        $qb = new \Cora\Db_MySQL();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [['users.role', '=', ':roles.role_id']], 'OUTER');

        $this->assertEquals('SELECT name FROM users OUTER JOIN roles ON (users.role = roles.role_id)', (string) $qb);
    }


    /**
    *  Check JOIN complex
    *
    *  @test
    */
    public function canJoinComplex()
    {
        $qb = new \Cora\Db_MySQL();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [
                ['users.role', '=', ':roles.role_id'],
                ['users.status', '=', 'active']
            ], 'OUTER');

        $this->assertEquals("SELECT name FROM users OUTER JOIN roles ON (users.role = roles.role_id AND users.status = 'active')", (string) $qb);
    }


    /**
    *  Check JOIN multiple
    *
    *  @test
    */
    public function canJoinMultiple()
    {
        $qb = new \Cora\Db_MySQL();
        $qb ->select('name')
            ->from('users')
            ->join('roles', [['users.role', '=', ':roles.role_id']], 'OUTER')
            ->join('users_meta', [['users.id', '=', ':users_meta.user_id']]);

        $this->assertEquals('SELECT name FROM users OUTER JOIN roles ON (users.role = roles.role_id) JOIN users_meta ON (users.id = users_meta.user_id)', (string) $qb);
    }


    /**
    *  Check that adding multiple WHERE fields works properly
    *
    *  @test
    */
    public function canWhereMultipleWithFunctionCalls()
    {
        $qb = new \Cora\Db_MySQL();
        $qb->select(['name', 'email'])
           ->from('users')
           ->where('status', 'active')
           ->where('money', ':debt', '>');

        $this->assertEquals("SELECT name, email FROM users WHERE (status = 'active') AND (money > debt)", (string) $qb);   // Direct. Fast.
    }


    // $qb->select(new DbFunction('SUM', 'money'))
    // $qb->select(new DbFunction('SUM', new DbFunction('CONCAT', 'name')))
    // DbFunction takes a name and value (which could be another function). Has toString method.

    // ALIASES
    // ->setColumns(['userId' => 'user_id', 'username' => 'name', 'email' => 'email']);
    // SELECT user.user_id AS 'userId'
}