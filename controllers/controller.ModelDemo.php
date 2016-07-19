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
    
    
    public function testRepo()
    {
        $this->repo = \Cora\RepositoryFactory::make('Note');
        $notes = $this->repo->findAll();
        foreach ($notes as $note) {
            echo $note->note.'<br>';
        }
        echo '<BR><BR><BR>';
        $this->repo = \Cora\RepositoryFactory::make(Note::class);
        $notes = $this->repo->findAll();
        foreach ($notes as $note) {
            echo $note->note.'<br>';
        }
    }
    
    
    public function testCreate()
    {
        $user = new \User();
        $user->name     = 'testMatt';
        $user->email    = 'testUser31@gmail.com';
        $user->type     = 'Member';
        $this->repo->save($user);
    }
    
    public function testDelete($id)
    {
        //$user = $this->repo->find($id);
        $this->repo->delete($id);
    }
    
    public function testCreate2()
    {
        $user = new \User('JoeTest', 'SuperAdmin');
        
        $user->job      = new Job('Librarian', 'Keeper of knowledge!');
        $user->location = new Location('JoesHouse', 'Portland');
        
        $user->guides   = new \Cora\ResultSet([
                            new Guide('The Dewey Decimal System'),
                            new Guide('How to Keep Kids Quiet')
                        ]);
        
        $user->notes    = new \Cora\ResultSet([
                            new Note('Librarian Note!'),
                            new Note('Librarian Note 2!')
                        ]);
        
        $user->articles = new \Cora\ResultSet([
                            new Article('Article Create2 #1'),
                            new Article('Article Create2 #2')
                        ]);
        $this->repo->save($user);
    }
    
    public function testCreate3()
    {
        $user = new \User('LocationTest', 'SuperAdmin');
        $user->location = new Location('JoesHouse', 'Portland');
        $this->repo->save($user);
    }
    
    public function testFetchClass($id = 64)
    {
        $user = $this->repo->find($id);
        //var_dump($user);
        //$user->model_dynamicOff = true;
        echo $user->name.'<br>';
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
        
        //var_dump($user);
        //var_dump($user->location);
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
        $user->guides = new \Cora\ResultSet([
                            new Guide('Building a House'),
                            new Guide('Building a House Vol2')
                        ]);
        $repo->save($user);
    }
    
    public function testUpdateMultiTableWithResultSet($id = 77) 
    {
        $repo = $this->repo;
        $user = $repo->find($id);
//        $user->notes = new \Cora\ResultSet([
//                            new Note('Hey O, Test Note #1'),
//                            new Note('Hey O, Test Note #2')
//                        ]);
        $user->notes->add(new Note('Hi MATT!'));
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
    
    public function testLightClass()
    {
        $this->db->where('name', 'testUser');
        $this->db->select(['id', 'type']);
        $user = $this->repo->findByQuery($this->db)->get(0);
        //$user->model_dynamicOff = true;
        echo $user->name;
        var_dump($user);
    }
    
    public function testInternal()
    {
        $this->db->where('name', 'testUser');
        $user = $this->repo->findByQuery($this->db)->get(0);
        echo $user->getName();
    }
}