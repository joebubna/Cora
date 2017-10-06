<?php
namespace Tests\Cora;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{   
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

        $query = $qb->getQuery();

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('SELECT name, email FROM users', $query);   // Direct. Fast.
    }


    /**
    *  Check that adding multiple SELECT fields works properly
    *
    *  @test
    */
    public function canSelectMultipleWithArray()
    {
        $qb = new \Cora\Db_MySQL();
        $qb->select(['name', 'email'])
        ->from('users');

        $query = $qb->getQuery();

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('SELECT name, email FROM users', $query);   // Direct. Fast.
    }


    /**
    *  Check that adding multiple SELECT fields works properly
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

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals("SELECT name, email FROM users WHERE (status = 'active') AND (money > debt)", (string) $qb);   // Direct. Fast.
    }


    // $qb->select(new DbFunction('SUM', 'money'))
    // $qb->select(new DbFunction('SUM', new DbFunction('CONCAT', 'name')))
    // DbFunction takes a name and value (which could be another function). Has toString method.

}