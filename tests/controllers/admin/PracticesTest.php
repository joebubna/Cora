<?php

class PracticesTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canViewListOfPractices()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Practices($this->app);
        $controller->index();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewEditPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Practices($this->app);
        $controller->edit(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewCreatePracticeForm()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Practices($this->app);
        $controller->create();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canCreatePractice()
    {
        $_POST['name'] = 'Test Practice';
        $_POST['type'] = 'Fuel Member';
        $_POST['status'] = 'Active';
        
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        
        // Pre
        $practice = $this->app->practices->findOneBy('name', $_POST['name']);
        $this->assertEmpty($practice);
        
        // Code
        $controller = new \Controllers\Admin\Practices($this->app);
        $controller->createPOST();
        
        // Post
        $practice = $this->app->practices->findOneBy('name', $_POST['name']);
        $this->assertNotEmpty($practice);
    }
    
    
    /**
     *  @test
     */
    public function canEditPractice()
    {
        // Set inputs
        $id = 1;
        $_POST['name'] = 'Test Practice';
        $_POST['type'] = 'Fuel Member Test';
        $_POST['status'] = 'Active Test';
        
        // Check pre-result
        $practice = $this->app->practices->find($id);
        $this->assertNotEquals($_POST['name'], $practice->name);
        $this->assertNotEquals($_POST['type'], $practice->type);
        $this->assertNotEquals($_POST['status'], $practice->status);
        
        // Execute code
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Practices($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $practice = $this->app->practices->find($id);
        $this->assertEquals($_POST['name'], $practice->name);
        $this->assertEquals($_POST['type'], $practice->type);
        $this->assertEquals($_POST['status'], $practice->status);
    }
}