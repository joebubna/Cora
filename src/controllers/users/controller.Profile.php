<?php
namespace Controllers\Users;

class Profile extends \Cora\App\Controller
{        
    protected $repo;
    protected $db;
    
    public function __construct($container = false)
    {
        parent::__construct($container);
        $this->repo = \Cora\RepositoryFactory::make('User');
        $this->db = $this->repo->getDb();
    }

    
    public function itemGET($id)
    {
        $this->view($id); 
    }
    
    
    public function view($id)
    {
        if (!$this->auth->access(new \Models\Auth\CanEditUser($id))) { return false; }
        
        $this->data->user = $this->repo->find($id);
        $this->data->title = 'View User';

        // Output View
        $this->_loadView(__FUNCTION__);   
    }
    
    
    public function edit($id)
    {
        if (!$this->auth->access(new \Models\Auth\CanEditUser($id))) { return false; }
        
        $this->load->library('Validate', $this, true); 
        $this->data->user = $this->repo->find($id);
        $this->data->title = 'Edit User';
        
        // Grab list of roles from DB
        $this->data->roles = $this->app->repository('Role')->findAll();

        //Grab our HTML
        $this->data->content = $this->load->view('users/profile/edit', $this->data, true);

        // Output View
        $this->_loadView(__FUNCTION__);      
    }

    
    public function editPOST($id)
    {
        if (!$this->auth->access(new \Models\Auth\CanEditUser($id))) { return false; }
        
        // Setup
        $this->load->library('Validate', $this, true); 
        $user = $this->repo->find($id);
        
        // Define custom check
        $this->Validate->def('accountExists', 'Libraries\\Cora\\Auth','accountExists', 'An account with that email already exists.', false, ['email', $user->email]);
        
        // Define validation rules.
        $this->Validate->rule('email', 'required|valid_email|accountExists|trim');
        $this->Validate->rule('firstName', 'required');
        $this->Validate->rule('lastName', 'required');
        
        // Initiate validation
        if ($this->Validate->run()) {        
            
            $user->firstName    = $this->input->post('firstName');
            $user->lastName     = $this->input->post('lastName');
            $user->email        = $this->input->post('email');
            $this->repo->save($user);

            $this->redirect->url("/users/profile/$user->id");
        }
        else {      
            // Call the main method to redisplay the form.
            $this->edit($id);
        }

    }
}