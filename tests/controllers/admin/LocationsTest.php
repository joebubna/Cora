<?php

class LocationsTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canViewListOfLocations()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Locations($this->app);
        $controller->index();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewLocationPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Locations($this->app);
        $controller->view(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewEditLocationPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Locations($this->app);
        $controller->edit(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewCreateLocationForm()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Locations($this->app);
        
        // Note that a Practice ID is required to create a location
        $controller->create(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canCreateLocation()
    {
        $_POST['name'] = 'Test Location Create 42';
        $_POST['phone'] = '555-55-5555';
        $_POST['address'] = '134 Fake St';
        $_POST['city'] = 'Camas';
        $_POST['state'] = 'WA';
        $_POST['zip'] = '97233';
        $_POST['status'] = 'Active';
        
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        
        // Pre
        $location = $this->app->locations->findOneBy('name', $_POST['name']);
        $this->assertEmpty($location);
        
        // Code
        $controller = new \Controllers\Admin\Locations($this->app);
        $controller->createPOST(1);
        
        // Post
        $location = $this->app->locations->findOneBy('name', $_POST['name']);
        $this->assertNotEmpty($location);
    }
    
    
    /**
     *  @test
     */
    public function canEditLocations()
    {
        // Set inputs
        $id = 1;
        $_POST['name'] = 'Test Location Edit 42';
        $_POST['phone'] = '555-55-5555';
        $_POST['address'] = '134 Fake St';
        $_POST['city'] = 'Camas';
        $_POST['state'] = 'WA';
        $_POST['zip'] = '97233';
        $_POST['status'] = 'Active Test';
        
        // Check pre-result
        $location = $this->app->locations->find($id);
        $this->assertNotEquals($_POST['name'], $location->name);
        $this->assertNotEquals($_POST['status'], $location->status);
        
        // Execute code
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Locations($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $location = $this->app->locations->find($id);
        $this->assertEquals($_POST['name'], $location->name);
        $this->assertEquals($_POST['status'], $location->status);
    }
}