<?php

class ProfileTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canViewUserProfile()
    {
        $this->app->dbBuilder->reset();
        $this->app->auth->login('provider@fuelmedical.com', 'test');
        $controller = new \Controllers\Users\Profile($this->app);
        $controller->view(1);
        $this->expectOutputString('');
    }
    
    /**
     *  @test
     */
    public function canViewEditPage()
    {
        $this->app->auth->login('provider@fuelmedical.com', 'test');
        $controller = new \Controllers\Users\Profile($this->app);
        $controller->edit(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function providerCannotViewEditPageOfOthers()
    {
        $this->app->auth->login('provider2@fuelmedical.com', 'test');
        $controller = new \Controllers\Users\Profile($this->app);
        $controller->edit(1);
        $this->expectOutputString('403');
    }
    
    
    /**
     *  @test
     */
    public function providerCannotEditOthers()
    {
        $this->app->dbBuilder->reset();
        
        // Set inputs
        $id = 1;
        $_POST['firstName'] = 'firstNameTest';
        $_POST['lastName'] = 'lastNameTest';
        $_POST['email'] = 'emailTest@fake.com';
        
        // Execute code
        $this->app->auth->login('provider2@fuelmedical.com', 'test');
        $controller = new \Controllers\Users\Profile($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $user = $this->app->repository('User')->find($id);
        $this->assertEquals('Bob', $user->firstName);
        $this->assertEquals('Ross', $user->lastName);
        $this->assertEquals('provider@fuelmedical.com', $user->email);
    }
    
    
    /**
     *  @test
     */
    public function userCanEditThemselves()
    {
        $this->app->dbBuilder->reset();
        
        // Set inputs
        $id = 1;
        $_POST['firstName'] = 'firstNameTest';
        $_POST['lastName'] = 'lastNameTest';
        $_POST['email'] = 'emailTest@fake.com';
        
        // Execute code
        $this->app->auth->login('provider@fuelmedical.com', 'test');
        $controller = new \Controllers\Users\Profile($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $user = $this->app->repository('User')->find($id);
        $this->assertEquals($_POST['firstName'], $user->firstName);
        $this->assertEquals($_POST['lastName'], $user->lastName);
        $this->assertEquals($_POST['email'], $user->email);
    }
}