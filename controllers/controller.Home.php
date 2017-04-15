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

    public function test()
    {
        $c = $this->app->collection;
        for ($i=0; $i<100000; $i++) {
            $c->add($i);
        }

        $time_start = microtime(true);
        $j = 0;
        while ($c->fetchOffset($j) != 999) {
            $j += 1;
        }
        echo $c->fetchOffset($j);
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        echo "Runtime of $time seconds\n";

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