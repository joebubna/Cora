<?php

class PatientsTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canViewListOfPatients()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Patients($this->app);
        $controller->index();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewPatientPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Patients($this->app);
        $controller->view(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewEditPatientPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Patients($this->app);
        $controller->edit(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewCreatePatientForm()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Patients($this->app);
        
        // Note that a Practice ID is required to create a Patient
        $controller->create(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canCreatePatient()
    {
        $_POST['practice']  = 1;
        $_POST['firstName'] = 'CreateTestBob';
        $_POST['lastName']  = 'CreateTestJones';
        $_POST['phone']     = '555-55-5555';
        $_POST['email']     = 'CreateTestBob@fakegmail.com';
        $_POST['dob']       = '1979-04-17';
        $_POST['address']   = '134 Fake St';
        $_POST['city']      = 'Camas';
        $_POST['state']     = 'WA';
        $_POST['zip']       = '97233';
        
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        
        // Pre
        $patient = $this->app->patients->findOneBy('email', $_POST['email']);
        $this->assertEmpty($patient);
        
        // Code
        $controller = new \Controllers\Admin\Patients($this->app);
        $controller->createPOST(1);
        
        // Post
        $patient = $this->app->patients->findOneBy('email', $_POST['email']);
        $this->assertNotEmpty($patient);
    }
    
    
    /**
     *  @test
     */
    public function canEditPatients()
    {
        // Set inputs
        $id = 1;
        $_POST['practice']  = 1;
        $_POST['firstName'] = 'EditTestBob';
        $_POST['lastName'] = 'EditTestJones';
        $_POST['phone'] = '555-55-5555';
        $_POST['email'] = 'EditTestBob@fakegmail.com';
        $_POST['dob'] = '1979-04-17';
        $_POST['address'] = '134 Fake St';
        $_POST['city'] = 'Camas';
        $_POST['state'] = 'WA';
        $_POST['zip'] = '97233';
        
        // Check pre-result
        $patient = $this->app->patients->find($id);
        $this->assertNotEquals($_POST['firstName'], $patient->firstName);
        $this->assertNotEquals($_POST['lastName'], $patient->lastName);
        
        // Execute code
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Patients($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $patient = $this->app->patients->find($id);
        $this->assertEquals($_POST['firstName'], $patient->firstName);
        $this->assertEquals($_POST['lastName'], $patient->lastName);
    }
}