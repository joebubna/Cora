<?php
namespace Tests\Cora;

class AmblendTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canCreateUser()
    {
        $this->app->dbBuilder->reset();
        $users = $this->app->tests->users;
        $this->assertEquals($users->count(), 0);
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);
        $this->assertEquals($users->count(), 1);
    }

}