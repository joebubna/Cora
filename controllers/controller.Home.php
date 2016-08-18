<?php
use Cora\Event;

class Home extends \MyApp {
    
    public function index() {
        $this->data->title = 'A Simple Form';
        $this->data->content = 'HOME PAGE';
        
        
        if ($this->session->user) {
            echo 'Logged in!';
        }
        $this->load->view('', $this->data);
    }
    
    public function indexPOST() {
        echo $_POST['data'];
    }
    
    public function test2()
    {
        $this->auth->access(new \Auth\LoggedIn);
        
        $repo = $this->app->repository('user');
        echo $repo->find(1)->username;
    }
    
    public function view($p1, $p2, $p3) {
        echo 'Yay<br>';
        echo $p1 . '<br>';
        echo $p2 . '<br>';
        echo $p3 . '<br>';
    }
    
    public function test() {
        
        $this->load->model('note');
        $this->load->model('task/note');
        
        $note = new Note();
        $taskNote = new Task\Note();
        
        echo $note->foo();
        echo '<br>';
        echo $taskNote->foo();
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