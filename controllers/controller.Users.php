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
    public function register($inputName, $inputType)
    {
        // Grab our inputs. We'll just use fake data instead of
        // actually capturing it from a form.
        $name = $inputName;  // $this->input->post('name');
        $type = $inputType; // $this->input->post('email');
        
        // Create a new User.
        $user = new User($name, $type);
        
        // Assign the user a job.
        $user->job = new Job('Athlete', 'Track and Field');
        
        // Save the user to the database.
        $this->repo->save($user);
        
        // When the user was saved to the database, AmBlend
        // assigned the object the unique ID the database provided it.
        // Let's echo this user's ID.
        echo $user->id;
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