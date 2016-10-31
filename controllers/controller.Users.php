<?php
namespace Controllers;

class Users extends \Cora\App\Controller
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
        $this->data->content = $this->load->view('users/register', $this->data, true);
        $this->load->view('', $this->data);
    }
    
    /**
     *  Process user creation.
     */
    public function registerPOST()
    {
        // Load validation library
        $this->load->library('Validate', $this, true); 
        
        // Define custom check
        $this->Validate->def('accountExists', 'Cora\\Auth','accountExists', 'An account with that username already exists.', false, 'email');
        
        // Define validation rules.
        $this->Validate->rule('email', 'required|accountExists|trim');
        $this->Validate->rule('password', 'required');
        $this->Validate->rule('password_confirm', 'required|matches[password]', 'Password Confirmation');
        
        // Initiate validation
        if ($this->Validate->run()) {        
            
            // Grab data
            $email      = $this->input->post('email');
            $password   = $this->input->post('password');
            
            // Create auth object and call for creation of user
            $userId = $this->auth->userCreate($email, $password);
            
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
        $this->Validate->rule('email', 'required|valid_email|trim');
        $this->Validate->rule('password', 'required');
        
        // Initiate validation
        if ($this->Validate->run()) {
            
            // Grab data
            $email      = $this->input->post('email');
            $password   = $this->input->post('password');
            $rememberMe = $this->input->post('rememberMe');
            
            // Attempt login
            $user = $this->auth->login($email, $password, $rememberMe);
            
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
        $this->Validate->rule('email', 'required|valid_email|trim');
        
         // Initiate validation
        if ($this->Validate->run()) {
            
            // Grab data
            $email = $this->input->post('email');
            
            // Grab user
            $user = $this->repo->findBy('email', $email)->get(0);
            
            // If User account exists, send password reset email
            if ($user) {
                // Generate a new reset token for this User
                $token = $this->auth->userResetTokenCreate($user->id);
                
                // Update our current object with the new token (so we don't have to refetch from DB)
                $user->resetToken = $token;
                
                // Fire Password Reset Event to handle other actions such as sending reset email.
                $this->event->fire(new \Event\PasswordReset($user, $this->app->mailer(), $this->load));
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
        // Grab Link Data
        $user_id = $this->input->get('id');
        $token = $this->input->get('token');
        
        // Grab User data.
        //$user = $this->repo->find($user_id);
        
        // Check for token match. FUTURE IMPROVEMENT: Make token expire after 24 hours.
        if ($this->auth->userResetTokenVerify($user_id, $token)) {
            $this->session->resetId = $user_id;
            //$this->resetPassword($user_id);
            $this->redirect->url('/users/resetPassword/');
        }
        else {
            $this->data->title = 'Forgot Password Verification';
            $this->data->content = $this->load->view('users/invalidToken', $this->data, true);
            $this->load->view('', $this->data);
        }
    }
    
    
    public function resetPassword()
    {
        if (!$this->session->resetId) {
            $this->redirect->url('/users/login/');    
        }     
        
        $this->load->library('Validate', $this, true); 
        $this->data->title = 'Reset Password';
        $this->_loadView(__FUNCTION__);
    }
    
    
    public function resetPasswordPOST()
    {
        // Load validation library
        $this->load->library('Validate', $this, true); 

        // Define validation rules.
        $this->Validate->rule('password', 'required');
        $this->Validate->rule('password_confirm', 'required|matches[password]', 'Password Confirmation');
        
        // Initiate validation
        if ($this->Validate->run()) {        
            
            // Grab data
            $password = $this->input->post('password');
            
            // Update password
            //$test = $_SESSION['resetId'];
            $this->auth->passwordUpdate($this->session->resetId, $password);
            $this->session->delete('resetId');
            
            // Make user login
            $this->data->notices[] = 'Password Updated!';
            $this->redirect->url('/users/login/');    
        }
        else {      
            // Passwords don't match. Try again.
            $this->resetPassword();
        }
    }
    
    
    public function logout()
    {
        $this->auth->logout();
        $this->redirect->url();
    }
    
    
    /**
     *  Display a user's profile.
     */
    // public function profile($id)
    // {
    //     $user = $this->repo->find($id);
    //     // echo $user->job->title;
    //     // echo $user->email;
    //     // var_dump($user);
    // }

//  MOVED TO controller.PROFILE.php
    // public function providerProfile($id)
    // {
    //     $this->data->user = $this->repo->find($id);
    //     $this->data->title = $this->repo->find($id)->firstName . ' ' . $this->repo->find($id)->lastName;

    //     //echo $user->job->title;
    //     // var_dump($user);
    //     // $this->data->title = user;

    //     //Grab our viewProviders HTML
    //     $this->data->content = $this->load->view('users/providerProfile', $this->data, true);

    //     // Load partial view and other data into our template.
    //     $this->load->view('', $this->data);    
    // }
}






