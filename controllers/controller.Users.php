<?php

class Users extends \MyApp
{
    protected $repo;
    protected $db;
    
    public function __construct($container = false)
    {
        parent::__construct($container);
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
        $this->Validate->def('accountExists', 'Cora\\Auth','accountExists', 'An account with that username already exists.', false, 'username');
        
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
            $user = $this->auth->login($username, $password, $rememberMe);
            
            if ($user) {
                $this->site->user = $user;
                $this->redirect->url();
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
     *  Forgot Password Form
     */
    public function forgotPassword()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Forgot Password';
        $this->_loadView(__FUNCTION__);
    }
    
    /**
     *  Forgot password process
     */
    public function forgotPasswordPOST()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Forgot Password';
        
        // Define validation rules.
        $this->Validate->rule('username', 'required|trim');
        
         // Initiate validation
        if ($this->Validate->run()) {
            
            // Grab data
            $username = $this->input->post('username');
            
            // Grab user
            $user = $this->repo->findBy('username', $username)->get(0);
            
            // If User account exists, send password reset email
            if ($user) {
                // Generate a new token for this User
                $token = $this->auth->userTokenCreate($user->id);
                
                // Update our current object with the new token (so we don't have to refetch from DB)
                $user->token = $token;
                
                // Fire Password Reset Event to handle other actions such as sending reset email.
                $this->event->fire(new \Event\PasswordReset($user, $this->app->mailer()));
            }
            else {
                $this->data->errors = ['No such account.'];
                $this->forgotPassword();
            }
        }
        else {
            $this->forgotPassword();
        }
    }
    
    
    /**
     *  Forgot Password verify token
     */
    public function forgotPasswordVerify()
    {
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Forgot Password';
        $this->_loadView(__FUNCTION__);
    }
    
    
    public function logout()
    {
        $this->auth->logout();
        $this->redirect->url();
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