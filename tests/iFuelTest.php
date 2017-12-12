<?php
namespace Tests;

class iFuelTest extends \Cora\App\TestCase
{   
    /**
     *  Check that it's possible to create a simple model located that doesn't inherit from another model.
     *  
     *  @test
     */
    public function canCreateSimpleModel()
    {
        $this->app->dbBuilder->reset();
        $practices = $this->app->repository('Ifuel\Practice');
        $businesses = $this->app->repository('Ifuel\Business');
        
        $business = new \Models\Ifuel\Business('Business1');
        $business->type = 'Lead';
        //$this->businesses->save($business);

        $practice = new \Models\Ifuel\Practice('Practice1');
        $practice->business = $business;
        $practices->save($practice);
        $practice->type = 'Member';

        $this->assertEquals('Member', $practice->business->type);
    }
}