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
        $businesses->save($business);

        $practice = new \Models\Ifuel\Practice('Practice1');
        $practice->business = $business;
        $practice->type = 'Member';
        $practices->save($practice);

        $practice = $practices->find(1);

        $this->assertEquals('Member', $practice->business->type);
    }
}