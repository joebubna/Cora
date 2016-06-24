<?php

class ModelDemo extends \MyApp {
    
    protected $repo;
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->db   = new \Cora\Db_MySQL();
        $this->repo = \Cora\RepositoryFactory::make('User');     
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
        $user = $this->repo->findBy($this->db)->get(0);
        if ($user->type == 'Member') {
            $user->type = 'Admin';
        }
        else {
            $user->type = 'Member';
        }
        $this->repo->save($user);
    }
    
    public function testUpdateById() 
    {
        $repo = $this->repo;
        $user = $repo->find(53);
        $user->name = 'Josiah';
        $repo->save($user);
    }
    
    public function testFetchClass()
    {
        $this->db->where('name', 'testUser');
        $user = $this->repo->findBy($this->db)->get(0);
        var_dump($user);
    }
    
    public function testLightClass()
    {
        $this->db->where('name', 'testUser');
        $this->db->select(['id', 'type']);
        $user = $this->repo->findBy($this->db)->get(0);
        var_dump($user);
    }
    
    public function testInternal()
    {
        $this->db->where('name', 'testUser');
        $user = $this->repo->findBy($this->db)->get(0);
        echo $user->getName();
    }
}