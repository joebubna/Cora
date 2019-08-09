<?php
namespace Tests;

class AutoloadTest extends TestCase
{   
    /**
     *  
     *
     *  @test
     */
    public function canLoadFromControllerFolder()
    {
        $c = new \Controllers\Test();
        $this->assertEquals(42, $c->verifyLoad());
    }


    /**
     *  
     *
     *  @test
     */
    public function canLoadSubController()
    {
        $c = new \Controllers\Api\Test();
        $this->assertEquals(7, $c->verifyLoad());
    }


    /**
     *  
     *
     *  @test
     */
    public function canLoadFromClassFolder()
    {
        $c = new \Classes\Test();
        $this->assertEquals(99, $c->verifyLoad());
    }

}