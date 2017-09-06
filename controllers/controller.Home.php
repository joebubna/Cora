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

    public function ctest() 
    {
        // $c = new \Cora\Collection([1,2,3,4,5]);
        // echo $c[4];             // Returns "5". Standard array access format.
        // echo $c->get(4);        // Returns "5". Required by PSR-11.
        // echo $c->offsetGet(4);  // Returns "5". Required by ArrayAccess interface.
        // echo $c->off4;          // Returns "5". Object access syntax.
        // echo $c[5];             // Returns null. No such offset.
        // echo "<BR><BR><BR>";


        $c = new \Cora\Collection(["one" => 1, "two" => 2, "three" => 3, "four" => 4, "five" => 5]);
        // echo $c[4];             // Returns "5". Standard array access format.
        // echo $c["five"];        // Returns "5". Associative array access format.
        // echo $c->off4;          // Returns "5". Object access format.
        // echo $c->five;          // Returns "5". Direct Object access format.
        //var_dump($c);

        $c->six = 6;
        // $c->six = function() { return 6; };
        var_dump($c);
        echo $c->six;
        var_dump($c);
        echo $c->off5;
        var_dump($c);
        echo "<BR><BR><BR>";

        // $c = new \Cora\Collection([
        //     ["name" => "Jake", "age" => 33],
        //     ["name" => "Bob", "age" => 42]
        // ], 'name');
        // echo $c[1]['age'];
        // echo $c['Bob']['age'];             // Returns "5". Standard array access format.
        // echo $c->get(1)['age'];        // Returns "5". Required by PSR-11.
        // echo $c->offsetGet(1)['age'];  // Returns "5". Required by ArrayAccess interface.
        // echo $c->Bob['age'];          // Returns "5". Object access syntax.
        // echo "<BR><BR><BR>";
    }

    public function ctest2()
    {
        $c = new \Cora\Collection(["one" => 1, "two" => 2, "three" => 3, "four" => 4, "five" => 5]);
        $c->add(6, "six");
        //$c->generateContent();
        echo $c["five"];
        echo $c["six"];
    }

    public function test()
    {
        //echo \Models\User::class;
        // $query = $this->app->{\Cora\Repository::class.\Models\User::class}->getDb()->limit(5);
        // $this->data->models = $this->app->users->findAll($query);
        // $this->data->modelFields = ['id', 'name', 'birthday'];
        // $this->data->content = $this->load->view('models/index', $this->data, true);
        // $this->load->view('template', $this->data);

        // $user = $this->app->{\Models\User::class}('Bob2');
        // echo $user->email;
        $hi = new \Cora\Database\DbString('Hello World');
        echo $hi;
    }

    public function factoryTest()
    {
        // Create a new collection
        $users = $this->app->collection();

        // Get a user model factory
        $userFactory = $this->app->getFactory(\Models\User::class);
        for ($i=0; $i < 5; $i++) {
            $users->add($userFactory->make("Bob$i"));
        }

        // This will output 5 users ranging from Bob0 to Bob4
        foreach($users as $user) {
            echo $user->email."<br>";
        }
    }

    public function indexTest()
    {
        $query = $this->app->users->getDb()->limit(5);
        $this->data->models = $this->app->users->findAll($query);
        $this->data->modelFields = ['id', 'name', 'birthday'];
        $this->data->content = $this->load->view('models/index', $this->data, true);
        $this->load->view('template', $this->data);
    }

    public function viewTest($id)
    {
        $this->data->model = $this->app->users->find($id);
        $this->data->content = $this->load->view('models/view', $this->data, true);
        $this->load->view('template', $this->data);
    }

    public function viewTestPOST($id)
    {
        $user = $this->app->users->find($id);
        $user->_populate($this->input->post());
        $user->save();
        $this->test($id);
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
        var_dump($collection->map(function($item) {
            return $item['balance'] * 2;
        }));

        // $collection = $this->app->collection([
        //     new \Models\Tests\Date('Debit', '10/10/1980'),
        //     new \Models\Tests\Date('Debit', '10/10/2001'),
        //     new \Models\Tests\Date('Deposit', '02/14/2008'),
        //     new \Models\Tests\Date('Debit', '10/10/1990'),
        //     new \Models\Tests\Date('Debit', '10/10/2003'),
        //     new \Models\Tests\Date('Deposit', '02/14/2004'),
        //     new \Models\Tests\Date('Debit', '02/14/1985'),
        //     new \Models\Tests\Date('Debit', '02/14/1994'),
        //     new \Models\Tests\Date('Deposit', '02/14/1974')
        // ]);

        // $collection->sort('timestamp');
        // echo $collection->get(0)->timestamp->format("m/d/Y");

        // echo "<br><br>";
        // foreach ($collection as $c) {
        //     echo $c->timestamp->format("m/d/Y")."<br>";
        // }
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