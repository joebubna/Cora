<?php
namespace Tests\Cora;

class ContainerTest extends \Cora\App\TestCase
{   
    /**
     *  Check that it's possible to add and retrieve a primitive using object syntax
     *
     *  @test
     */
    public function canStorePrimitivesUsingObjectSyntax()
    {
        $collection = $this->app->collection;
        $this->assertEquals(0, $collection->count());
        $collection->add('Hello World');
        $this->assertEquals(1, $collection->count());

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('Hello World', $collection->get("off0"));   // Direct. Fast.

        // Check adding a number. 
        $collection->add(2);
        $this->assertEquals(2, $collection->get("off1"));
    }


    /**
     *  Check that it's possible to add and retrieve a primitive using methods.
     *
     *  @test
     */
    public function canStorePrimitivesUsingMethod()
    {
        $collection = $this->app->collection;
        $this->assertEquals(0, $collection->count());
        $collection->add('Hello World');
        $this->assertEquals(1, $collection->count());

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('Hello World', $collection->get(0));        // Indirect, loops.

        // Check adding a number. 
        $collection->add(2);
        $this->assertEquals(2, $collection->get(1));
    }


    /**
     *  Check that it's possible to add and retrieve a primitive using array syntax
     *
     *  @test
     */
    public function canStorePrimitivesUsingArraySyntax()
    {
        $collection = $this->app->collection;
        $this->assertEquals(0, $collection->count());
        $collection->add('Hello World');
        $this->assertEquals(1, $collection->count());

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('Hello World', $collection[0]);

        // Check adding a number. 
        $collection->add(2);
        $this->assertEquals(2, $collection[1]);
    }


    /**
     *  Check that it's possible to remove a primitive using Object syntax.
     *
     *  @test
     */
    public function canRemovePrimitiveUsingObjectSyntax()
    {
        $collection = $this->app->collection;
        $this->assertEquals(0, $collection->count());
        $collection->add('Hello World');
        $this->assertEquals(1, $collection->count());

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('Hello World', $collection->get("off0"));   // Direct. Fast.

        // Check adding a number. 
        $collection->add(2);
        $this->assertEquals(2, $collection->get("off1"));

        // Remove the first element, then check that new first element is correct. 
        $collection->delete('off0');
        $this->assertEquals(1, $collection->count());
        $this->assertEquals(2, $collection->get(0)); 
    }


    /**
     *  Check that it's possible to remove a primitive using Object syntax.
     *
     *  @test
     */
    public function canRemovePrimitiveUsingMethod()
    {
        $collection = $this->app->collection;
        $this->assertEquals(0, $collection->count());
        $collection->add('Hello World');
        $this->assertEquals(1, $collection->count());

        // Check that the value can be retrieved directly via index or indirectly via offset
        $this->assertEquals('Hello World', $collection->get(0));        // Indirect, loops.

        // Check adding a number. 
        $collection->add(2);
        $this->assertEquals(2, $collection->get(1));

        // Remove the first element, then check that new first element is correct. 
        $collection->delete(0);
        $this->assertEquals(1, $collection->count());
        $this->assertEquals(2, $collection->get('off0')); 
    }


    /**
     *  Check that it's possible to return a subset of results when dealing with objects.
     *
     *  @test
     */
    public function canReturnObjectCollectionSubset()
    {
        $collection = $this->app->collection([
            new \Models\Tests\Date('Debit', '10/10/1980'),
            new \Models\Tests\Date('Debit', '10/10/2001'),
            new \Models\Tests\Date('Deposit', '02/14/2008'),
            new \Models\Tests\Date('Debit', '10/10/1990'),
            new \Models\Tests\Date('Debit', '10/10/2003'),
            new \Models\Tests\Date('Deposit', '02/14/2004')
        ]);
        $this->assertEquals(6, $collection->count());
        $this->assertEquals(4, count($collection->where('name', 'Debit')));
        $this->assertEquals(2, count($collection->where('name', 'Deposit')));
        $this->assertEquals(4, count($collection->where('timestamp', new \DateTime('01/01/2000'), '>=')));
    }


    /**
     *  Check that collection can be sorted.
     *
     *  @test
     */
    public function canSortCollection()
    {
        $collection = $this->app->collection([
            new \Models\Tests\Date('Debit', '10/10/1980'),
            new \Models\Tests\Date('Debit', '10/10/2001'),
            new \Models\Tests\Date('Deposit', '02/14/2008'),
            new \Models\Tests\Date('Debit', '10/10/1990'),
            new \Models\Tests\Date('Debit', '10/10/2003'),
            new \Models\Tests\Date('Deposit', '02/14/2004'),
            new \Models\Tests\Date('Debit', '02/14/1985'),
            new \Models\Tests\Date('Debit', '02/14/1994'),
            new \Models\Tests\Date('Deposit', '02/14/1974')
        ]);
        $collection->sort('timestamp');
        $this->assertEquals('02/14/1974', $collection->get(0)->timestamp->format("m/d/Y"));
        $this->assertEquals('02/14/2008', $collection->get(8)->timestamp->format("m/d/Y"));

        $collection->sort('timestamp', 'asc');
        $this->assertEquals('02/14/1974', $collection->get(8)->timestamp->format("m/d/Y"));
        $this->assertEquals('02/14/2008', $collection->get(0)->timestamp->format("m/d/Y"));
        
        // foreach($collection->sort('name') as $item) {
        //     echo "\n    ".$item->name." - ".$item->timestamp->format('Y-m-d');
        // }
        // echo "\n";
        // foreach($collection->sort('name', 'asc')->where('name', 'Debit') as $item) {
        //     echo "\n    ".$item->name." - ".$item->timestamp->format('Y-m-d');
        // }
    }

}