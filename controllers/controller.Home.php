<?php
namespace Controllers;
use Cora\Event;

class Home extends \Cora\App\Controller {
    
    protected $testArray = [];
    
    public function index() {
        $this->data->title = 'A Simple Form';
        $this->data->user = $this->site->user;
        
        // Grab our homepage HTML
        $this->data->content = $this->load->view('home/index', $this->data, true);
        
        // Load partial view and other data into our template.
        $this->load->view('template', $this->data);
    }
    
    public function tester() {
        $stuff = new \stdClass();
        $stuff->title = 'A Simple Form';
        $stuff->content = 'Hello MATT';
        
        // Grab our homepage HTML
        $this->data->content = $this->load->view('home/index', $stuff, true);
        
        // Load partial view and other data into our template.
        $this->load->view('template', $stuff);
    }
    public function indexPOST() {
        echo $_POST['data'];
    }
    
    public function test1()
    {
        $user = $this->app->users->findOneBy('email', 'coraTestUser2@gmail.com');
        var_dump($user->parent);
        foreach ($user->parent->comments as $comment) {
            echo $comment->text;
        }
    }
    
    public function test1_1()
    {
        $this->testArray = ['Color1', 'Color2'];
        print_r($_POST['list']);
    }
    
    public function test2()
    {
        $this->auth->access(new \Auth\LoggedIn);
        
        $repo = $this->app->repository('user');
        echo $repo->find(1)->email;
    }
    
    public function test3()
    {
        $this->auth->access([new \Auth\LoggedIn, new \Auth\CanAccessAdmin]);
        
        $repo = $this->app->repository('user');
        echo $repo->find(1)->email;
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