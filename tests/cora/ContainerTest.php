<?php
namespace Tests\Cora;

class ContainerTest extends \Cora\App\TestCase
{   
    /**
     *  Check that it's possible to add and retrieve a primitive using Property Name
     *
     *  @test
     */
    public function canStorePrimitivesUsingPropertyName()
    {
        $collection = new \Cora\Collection();
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
    public function canStorePrimitivesUsingOffsetMethod()
    {
        $collection = new \Cora\Collection();
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
    public function canStorePrimitivesUsingArrayOffset()
    {
        $collection = new \Cora\Collection();
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
     *  Check that it's possible to remove a primitive using Property Name
     *
     *  @test
     */
    public function canRemovePrimitiveUsingPropertyName()
    {
        $collection = new \Cora\Collection();
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
     *  Check that it's possible to remove a primitive using offset number.
     *
     *  @test
     */
    public function canRemovePrimitiveUsingOffset()
    {
        $collection = new \Cora\Collection();
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
        $collection = new \Cora\Collection([
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
     *  Check that it's possible to set the key/property you want to be accessor
     *
     *  @test
     */
    public function canSetAccessKeyForObject()
    {
        $collection = new \Cora\Collection([
            new \Models\Tests\User('User1', 'Type1'),
            new \Models\Tests\User('User2', 'Type1'),
            new \Models\Tests\User('User3', 'Type2'),
            new \Models\Tests\User('User4', 'Type2'),
            new \Models\Tests\User('User5', 'Type1'),
            new \Models\Tests\User('User6', 'Type3')
        ], 'name');
        $this->assertEquals(6, $collection->count());

        $subset = $collection->where('type', 'Type2');
        $this->assertEquals(2, count($subset));
        $this->assertEquals('User3', $subset->User3->name);
    }


    /**
     *  Check that it's possible to set the key/property you want to be accessor
     *
     *  @test
     */
    public function canSetAccessKeyForArray()
    {
        $collection = new \Cora\Collection([
            ['name'=>'User1', 'type'=>'Type1'],
            ['name'=>'User2', 'type'=>'Type1'],
            ['name'=>'User3', 'type'=>'Type2'],
            ['name'=>'User4', 'type'=>'Type2'],
            ['name'=>'User5', 'type'=>'Type1'],
            ['name'=>'User6', 'type'=>'Type3']
        ], 'name');
        $this->assertEquals(6, $collection->count());

        $subset = $collection->where('type', 'Type2');
        $this->assertEquals(2, count($subset));
        $this->assertEquals('User3', $subset->User3['name']);
    }


    /**
     *  Check that collection can be sorted.
     *
     *  @test
     */
    public function canSortCollection()
    {
        $collection = new \Cora\Collection([
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
    }


    /**
     *  Check that it's possible to get the max value from collection.
     *
     *  @test
     */
    public function canGetMaxValue()
    {
        $collection = new \Cora\Collection([
            ['name'=>'User1', 'balance'=>200],
            ['name'=>'User2', 'balance'=>100],
            ['name'=>'User3', 'balance'=>500],
            ['name'=>'User4', 'balance'=>400],
            ['name'=>'User5', 'balance'=>900],
            ['name'=>'User6', 'balance'=>200]
        ], 'name');
        $max = $collection->max('balance');
        $this->assertEquals('User5', $max['name']);
    }


    /**
     *  Check that it's possible to get the min value from collection.
     *
     *  @test
     */
    public function canGetMinValue()
    {
        $collection = new \Cora\Collection([
            ['name'=>'User1', 'balance'=>200],
            ['name'=>'User2', 'balance'=>100],
            ['name'=>'User3', 'balance'=>500],
            ['name'=>'User4', 'balance'=>400],
            ['name'=>'User5', 'balance'=>900],
            ['name'=>'User6', 'balance'=>200]
        ], 'name');
        $min = $collection->min('balance');
        $this->assertEquals('User2', $min['name']);
    }


    /**
     *  Check that it's possible to map the collection.
     *
     *  @test
     */
    public function canMap()
    {
        $collection = new \Cora\Collection([
            ['name'=>'User1', 'balance'=>200],
            ['name'=>'User2', 'balance'=>100],
            ['name'=>'User3', 'balance'=>500],
            ['name'=>'User4', 'balance'=>400],
            ['name'=>'User5', 'balance'=>900],
            ['name'=>'User6', 'balance'=>200]
        ], 'name');
        $mc = $collection->map(function($item) {
            $item['balance'] = $item['balance'] * 2;
            return $item;
        });
        $this->assertEquals(200, $mc->off1['balance']);
        $this->assertEquals('User2', $mc->off1['name']);

        $this->assertEquals(1800, $mc->off4['balance']);
        $this->assertEquals('User5', $mc->off4['name']);
    }


    /**
     *  Check that it's possible to map the collection.
     *
     *  @test
     */
    public function canFilter()
    {
        $collection = new \Cora\Collection([
            ['name'=>'User1', 'balance'=>200],
            ['name'=>'User2', 'balance'=>100],
            ['name'=>'User3', 'balance'=>500],
            ['name'=>'User4', 'balance'=>400],
            ['name'=>'User5', 'balance'=>900],
            ['name'=>'User6', 'balance'=>200]
        ], 'name');
        $mc = $collection->filter(function($item) {
            return $item['balance'] > 200;
        });
        $this->assertEquals(3, $mc->count());
        $this->assertEquals(500, $mc->off0['balance']);
        $this->assertEquals('User3', $mc->off0['name']);
    }

}