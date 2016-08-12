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
     *  Register User form.
     */
    public function register()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Register';
        $this->_loadView(__FUNCTION__);
        
        //$this->data->content = $this->load->view('users/register', $this->data, true);
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
        $this->Validate->def('accountExists', 'Library\\Auth','accountExists', 'An account with that username already exists.', false, 'username');
        
        // Define validation rules.
        $this->Validate->rule('username', 'required|accountExists|trim');
        $this->Validate->rule('email', 'required|trim');
        $this->Validate->rule('password', 'required');
        $this->Validate->rule('password_confirm', 'required|matches[password]', 'Password Confirmation');
        
        // Initiate validation
        if ($this->Validate->run()) {        
            // Submit was successful!
            
            // Grab data
            $username   = $this->input->post('username');
            $email      = $this->input->post('email');
            $password   = $this->input->post('password');
            
            // Create auth object and call for creation of user
            $this->auth->userCreate($username, $email, $password);
            
            // Fire user created event
            
        }
        else {      
            // Call the main method to redisplay the form.
            $this->register();
        }
    }
    
    
    /**
     *  Login Form
     */
    public function login()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Login';
        $this->_loadView(__FUNCTION__);
    }
    
    /**
     *  Login Process
     */
    public function loginPOST()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Login';
        
        // Define validation rules.
        $this->Validate->rule('username', 'required|trim');
        $this->Validate->rule('password', 'required');
        
         // Initiate validation
        if ($this->Validate->run()) {
            
            // Grab data
            $username   = $this->input->post('username');
            $email      = $this->input->post('email');
            $password   = $this->input->post('password');
            $rememberMe = $this->input->post('rememberMe');
            
            // Attempt login
            $user = $this->auth->login($username, $password, $rememberMe)
            
            if ($user) {
                $echo 'Valid username and password';
            }
            else {
                
                $this->data->errors = ['Invalid username and password.'];
                $this->login();
            }
        }
        else {
            $this->login();
        }
    }
    
    
    /**
     *  Display a user's profile.
     */
    public function profile($id)
    {
        $user = $this->repo->find($id);
        //echo $user->job->title;
        var_dump($user);
    }
}