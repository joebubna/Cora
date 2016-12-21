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
        $user = $this->app->users->findOneBy('email', 'coraTestUser1@gmail.com');
        var_dump($user->comments);
        foreach ($user->comments as $comment) {
            echo "{$comment->text}<br>";
        }

        // foreach ($user->comments->where('status', 'Deleted', '<>') as $comment) {
        //     echo "{$comment->text}<br>";
        // }
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

    public function fizzBuzz()
    {
        $result[1] = 'Fizz';
        $result[2] = 'Buzz';
        $result[3] = 'FizzBuzz';
        
        for ($i=1; $i <= 100; $i++) {
            $result[0] = $i;
            
            $value = 0;
            $value += $i % 3 == 0 ? 1 : 0;
            $value += $i % 5 == 0 ? 2 : 0;
            echo $result[$value].'<br>';
        }
    }

    public function fizzBuzz2()
    {
        for ($i=1; $i <= 100; $i++) {
            $result = '';
            if ($i % 3 == 0 && $i % 5 == 0) {
                $result = 'FizzBuzz';
            }
            else if ($i % 3 == 0) {
                $result = 'Fizz';
            }
            else if ($i % 5 == 0) {
                $result = 'Buzz';
            }
            else {
                $result = $i;
            }
            echo $result.'<br>';
        }
    }

    public function fizzBuzz3($i = 1)
    {
        $result = '';
        if ($i % 3 == 0 && $i % 5 == 0) {
            $result = 'FizzBuzz';
        }
        else if ($i % 3 == 0) {
            $result = 'Fizz';
        }
        else if ($i % 5 == 0) {
            $result = 'Buzz';
        }
        else {
            $result = $i;
        }
        echo $result.'<br>';
        
        $i += 1;
        if ($i <= 100) {
            $this->fizzBuzz3($i);
        }
    }

    public function fizzBuzz4()
    {
        for ($i=0; $i < 10; $i++) {
            for ($j=1; $j <= 10; $j++) {
                $result = '';
                $value = ($i * 10) + $j;

                if ($value % 3 == 0 && $value % 5 == 0) {
                    $result = 'FizzBuzz';
                }
                else if ($value % 3 == 0) {
                    $result = 'Fizz';
                }
                else if ($value % 5 == 0) {
                    $result = 'Buzz';
                }
                else {
                    $result = $value;
                }
                echo $result.'<br>';
            }
        }
    }
}