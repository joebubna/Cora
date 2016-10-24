<?php

class ProvidersTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canViewListOfProviders()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Providers($this->app);
        $controller->index();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewListOfProvidersByPractice()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Providers($this->app);
        $controller->byPractice(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewCreateProviderForm()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Providers($this->app);
        $controller->create();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canCreateProvider()
    {
        $_POST['email'] = $email = 'test1234567890@internalTest.com';
        $_POST['firstName'] = 'FakeNameFirst';
        $_POST['lastName'] = 'FakeNameLast';
        $_POST['practice'] = 1;
        
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        
        // Resources
        $auth = $this->app->auth();
        
        // Pre
        $this->assertFalse($auth::accountExists($email));
        
        // Code
        $controller = new \Controllers\Admin\Providers($this->app);
        $controller->createPOST();
        
        // Post
        $this->assertTrue($auth::accountExists($email));
        
        // Try creating a duplicate user
        $controller->createPOST();
        
        // Post
        $this->assertEquals($this->app->repository('User')->findBy('email', $email)->count(), 1, 'A duplicate user was created!');
    }
    
    
    /**
     *  @test
     */
    public function canViewEditPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Providers($this->app);
        $controller->edit(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canEditProvider()
    {
        // Set inputs
        $id = 1;
        $_POST['firstName'] = 'firstNameTest';
        $_POST['lastName'] = 'lastNameTest';
        $_POST['email'] = 'emailTest@fake.com';
        $_POST['practice'] = 1;
        
        // Execute code
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Providers($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $provider = $this->app->repository('Provider')->find($id);
        $this->assertEquals($_POST['firstName'], $provider->user->firstName);
        $this->assertEquals($_POST['lastName'], $provider->user->lastName);
        $this->assertEquals($_POST['email'], $provider->user->email);
        $this->assertEquals($_POST['practice'], $provider->practice->id);
    }
}