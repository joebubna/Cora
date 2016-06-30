<?php
use \Task\Note;

class ModelDemo extends \MyApp {
    
    protected $repo;
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->repo = \Cora\RepositoryFactory::make('User');
        $this->db = $this->repo->getDb();
    }
    
    public function index() {
        $user = new \User();
        
        $this->data->title = 'A Simple Form';
        $this->data->content = 'Test';
        $user = new \User();
        $this->load->view('', $this->data);
    }
    
    
    public function testCreate()
    {
        $user = new \User();
        $user->name     = 'testUser';
        $user->email    = 'testUser31@gmail.com';
        $user->type     = 'Member';
        $this->repo->save($user);
    }
    
    public function testCreate2()
    {
        $user = new \User('Joe', 'SuperAdmin');
        $this->repo->save($user);
    }
    
    public function testUpdateByCustom()
    {
        $this->db->where('name', 'testUser');
        $user = $this->repo->findByQuery($this->db)->get(0);
        if ($user->type == 'Member') {
            $user->type = 'Admin';
        }
        else {
            $user->type = 'Member';
        }
        $this->repo->save($user);
    }
    
    public function testUpdateSingleRefTable() 
    {
        $repo = $this->repo;
        $user = $repo->find(64);
        $user->job = new Job('Construction', 'Building things.');
        $repo->save($user);
    }
    
    public function testUpdateMultiRefTableWithResultSet() 
    {
        $repo = $this->repo;
        $user = $repo->find(64);
        $user->guides = new \Cora\ResultSet(new Guide('Building a Farm'));
        $repo->save($user);
    }
    
    public function testUpdateMultiRefTableWithSingleObject() 
    {
        $repo = $this->repo;
        $user = $repo->find(64);
        $user->guides = new Guide('Building a House');
        $repo->save($user);
    }
    
    public function testUpdateByIdExisting() 
    {
        $repo = $this->repo;
        $user = $repo->find(64);
        if ($user->location->city == 'Portland') {
            $user->location->city = 'Camas';
        }
        else {
            $user->location->city = 'Portland';
        }
        $repo->save($user);
    }
    
    public function testUpdateById() 
    {
        $repo = $this->repo;
        $user = $repo->find(64);
        $user->location = new Location('Toms House', 'Portland');
        //$user->location->city = 'Camas';
        $repo->save($user);
    }
    
    public function testFetchClass()
    {
        $this->db->where('name', 'testUser');
        $user = $this->repo->findByQuery($this->db)->get(0);
//        var_dump($user);
        echo $user->location->name.'<br>';
        foreach ($user->articles as $article) {
            echo $article->title.'<br>';
        }
        foreach($user->guides as $guide) {
            echo $guide->title.'<br>';
        }
        echo $user->job->title.'<br>';
        foreach($user->notes as $note) {
            echo $note->note.'<br>';
        }
//        var_dump($user);
//        var_dump($user->location);
    }
    
    public function testLightClass()
    {
        $this->db->where('name', 'testUser');
        $this->db->select(['id', 'type']);
        $user = $this->repo->findByQuery($this->db)->get(0);
        var_dump($user);
    }
    
    public function testInternal()
    {
        $this->db->where('name', 'testUser');
        $user = $this->repo->findByQuery($this->db)->get(0);
        echo $user->getName();
    }
}