<?php
namespace Tests\Cora;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{   
    /**
     *  Check that adding multiple SELECT fields works properly
     *
     *  @test
     */
    public function canSelectMultiple()
    {
        $collection = new \Cora\Collection();

        // Check that the value can be retrieved directly via index or indirectly via offset
        //$this->assertEquals('Hello World', $collection->get("off0"));   // Direct. Fast.
    }

}