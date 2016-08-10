<?php

class Users extends \MyApp
{
    protected $repo;
    protected $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->repo = \Cora\RepositoryFactory::make('User');
        $this->db = $this->repo->getDb();
    }
    
    /**
     *  Create a new user.
     */
    public function register()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Register';
        $this->_loadView(__FUNCTION__);
        //$this->data->content = $this->load->view('forms/articles_create', $this->data, true);
        //$this->load->view('', $this->data);
    }
    
    /**
     *  Process user creation.
     */
    public function registerPOST()
    {
        // Load validation library
        $this->load->library('Validate', $this, true); 
        
        // Define custom check
        $this->Validate->def('accountExists', 'Library\\Auth','accountExists', 'An account with that username already exists.', false);
        
        // Define validation rules.
        $this->Validate->rule('username', 'required|accountExists|trim');
        $this->Validate->rule('email', 'required|trim');
        $this->Validate->rule('password', 'required');
        $this->Validate->rule('passwordConfirm', 'required|matches[password]', 'Password Confirmation');
        
        // Initiate validation
        if ($this->Validate->run()) {        
            // Submit was successful!
            $user = new User($name, $type);
            
            // Save the user to the database.
            $this->repo->save($user);
        }
        else {      
            // Call the main method to redisplay the form.
            $this->register();
        }
    }
    
    /**
     *  Display a user's profile.
     *  In this case, just echo their job title.
     */
    public function profile($id)
    {
        $user = $this->repo->find($id);
        //echo $user->job->title;
        var_dump($user);
    }
    
    public function lightClassDemo($id)
    {
        $this->db->where('id', $id);
        $this->db->select(['id', 'name']);
        $user = $this->repo->findOne($this->db);  
        var_dump($user);
    }
    
    public function subsetDemo()
    {
        $this->db->where('type', 'Admin');
        $users = $this->repo->findAll($this->db); 
        
        foreach ($users as $user) {
            echo $user->name.'<br>';
        }
    }
    
    public function joinDemo($id)
    {
        $this->db->where('users.id', $id)
                 ->select(['users.id', 'users.name', 'jobs.title'])
                 ->join('ref_users_jobs', [['users.id', '=', 'ref_users_jobs.user']])
                 ->join('jobs', [['ref_users_jobs.job', '=', 'jobs.id']]);
        $user = $this->repo->findOne($this->db);  
        echo $user->title;
    }
}