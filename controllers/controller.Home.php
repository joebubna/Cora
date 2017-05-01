<?php
namespace Controllers;
use Cora\Event;

class Home extends \Cora\App\Controller {
    
    public function index() {
        $this->data->title = 'A Simple Form';
        $this->data->user = $this->site->user;
        
        // Grab our homepage HTML
        $this->data->content = $this->load->view('home/index', $this->data, true);
        
        // Load partial view and other data into our template.
        $this->load->view('template', $this->data);
    }

    public function test2()
    {
        $collection = new \Cora\Collection([
            ['name'=>'User1', 'balance'=>200],
            ['name'=>'User2', 'balance'=>100],
            ['name'=>'User3', 'balance'=>500],
            ['name'=>'User4', 'balance'=>400],
            ['name'=>'User5', 'balance'=>900],
            ['name'=>'User6', 'balance'=>200]
        ], 'name');
        $max = $collection->min('balance');
        var_dump($max);
    }

    public function test()
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
        echo $collection->get(0)->timestamp->format("m/d/Y");

        echo "<br><br>";
        foreach ($collection as $c) {
            echo $c->timestamp->format("m/d/Y")."<br>";
        }
        
        // $c = $this->app->collection;
        // $a = [];
        // for ($i=0; $i<100000; $i++) {
        //     $c->add($i);
        //     $a[] = $i;
        // }

        // $time_start = microtime(true);
        // $j = 0;
        // while ($c->fetchOffset($j) != 999) {
        //     $j += 1;
        // }
        // echo $c->fetchOffset($j);
        // $time_end = microtime(true);
        // $time = $time_end - $time_start;
        // echo "Runtime of $time seconds\n";
        // echo "<br>";
    }
    
    public function view($p1, $p2, $p3 = 'bob') {
        echo $p1 . '<br>';
        echo $p2 . '<br>';
        echo $p3 . '<br>';
    }

    public  function viewPOST()
    {
        echo 'This is a POST';
    }
    
    public function eventSetup() 
    {        
        $user = new \User('Joe', 'SuperAdmin');
        $this->event->fire(new \Event\UserRegistered($user));
        
        
        $this->event->listenFor('customEvent', function($event) {
            echo $event->input->name.'<br>';
        });
        $this->event->listenFor('customEvent', function($event) {
            echo 'Higher Priority!<br>';
        }, 1);
        $this->event->fire(new Event('customEvent', $user));
    }

    
}