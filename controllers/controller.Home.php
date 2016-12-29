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
    
    public function view($p1, $p2, $p3) {
        echo 'Yay<br>';
        echo $p1 . '<br>';
        echo $p2 . '<br>';
        echo $p3 . '<br>';
    }
    
    public function test() {
        $db1 = $this->app->db;
        $db2 = $this->app->db(false, $db1);

        $db1->startTransaction();
        //$db2->startTransaction();
        echo 'HI';
        $db2->insert('firstName')
            ->into('users')
            ->values(['transTest'])
            ->exec();
        //$db1->commit();
        $db1->rollBack();
    }
    
    public function eventSetup() 
    {        
        $user = new \User('Joe', 'SuperAdmin');
        $this->event->fire(new \Event\RegisterUser($user));
        
        
        $this->event->listenFor('customEvent', function($event) {
            echo $event->input->name.'<br>';
        });
        $this->event->listenFor('customEvent', function($event) {
            echo 'Higher Priority!<br>';
        }, 1);
        $this->event->fire(new Event('customEvent', $user));
    }

    
}