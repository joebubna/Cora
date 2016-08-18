<?php
namespace Cora;

class Auth
{
    // Data
    protected $userId;
    protected $secureLogin;
    protected $authField;
    
    // Dependencies
    protected $repo;
    protected $db;
    protected $event;
    protected $session;
    protected $cookie;
    protected $redirect;
    
    
    public function __construct($userId = false, $secureLogin = false, $authField = 'username', $repo, $event, $session, $cookie, $redirect)
    {
        // Dependencies
        $this->repo = $repo;
        $this->db = $this->repo->getDb();
        $this->event = $event;
        $this->session = $session;
        $this->cookie = $cookie;
        $this->redirect = $redirect;
        
        // The unique field to use for authentication. Usually 'username' or 'email'.
        $this->authField = $authField;
        
        // If user authentication details were passed, use them.
        $this->userId = $userId;
        $this->secureLogin = $secureLogin;
        
        // If no user is logged in, try a passive (insecure) login from Cookie.
        if ($this->userId == false) {
            $this->insecureLogin($this->cookie->user, $this->cookie->token);
        }
    }
    
    
    public static function accountExists($authValue = false, $authField = false)
    {
        $repo = \Cora\RepositoryFactory::make('User');
        $numberOfMatchingUsers = $repo->findBy($authField, $authValue)->count();
        
        return $numberOfMatchingUsers > 0 ? true : false;
    }
    
    
    /**
     *  Returns true or false to whether the current user has access.
     */
    public function accessCheck($authModelInput)
    {
        // Default to allowing access.
        $permission = true;
        
        // If an array of Auth Models was passed in.
        // Check each permission, if ANY returns false, deny permission.
        if (is_array($authModelInput)) {
            foreach ($authModelInput as $authModel) {
                if ($authModel->handle($this, $this->session->user) == false) {
                    $permission = false;
                }
            } 
        }
        
        // If a single auth model.
        else {
            $authModel = $authModelInput;
            $permission = $authModel->handle($this, $this->session->user);
        }
        //echo $permission ? 'YES' : 'NO';
        return $permission;
    }
    
    /**
     *  Checks whether or not the current user has access, and redirects if necessary.
     */
    public function access($authModelInput) {
        if ($this->accessCheck($authModelInput)) {
            return true;
        }
        else if (!isset($this->session->user)) {
            // Redirect to login page.
            // With Saved URL for redirect after login.
            $this->redirect->saveUrl();
            $this->redirect->goto('/users/login');
        }
        else {
            // Show Forbidden 403 code.
            $error = new \Cora\Error();
            $error->handle('403');
            exit;
        }
    }
    
    
    public function hasGroupMembership($userId, $name)
    {
        
    }
    
    
    public function hasPermission($userId, $name, $group = false)
    {
        
    }
    
    
    public function userCreate($username, $email, $plainTextPassword)
    {
        // Hash password
        $hashedPassword = $this->passwordCreate($plainTextPassword);
        
        // Create User
        $user = new \User($username, $email, $hashedPassword);
            
        // Save the user to the database.
        $this->repo->save($user);
        
        return $user->id;
    }
    
    
    public function userDelete($userId)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->delete();
            return true;
        }
        return false; 
    }
    
    
    public function userTokenCreate($userId)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->token = $this->tokenCreate();
            $this->repo->save($user);
            return true;
        }
        return false; 
    }
    
    
    public function userTokenVerify($userId, $token)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            if ($user->token == $token) {
                return true;
            }
        }
        return false; 
    }
    
    
    public function passwordUpdate($userId, $plainTextPassword)
    {
        $user = $this->repo->find($userId);
        
        if ($user) {
            $user->password = $this->passwordCreate($plainTextPassword);
            $this->repo->save($user);
            return true;
        }
        return false; 
    }
    
    
    /**
     *  Normal login with a username and password.
     */
    public function login($authField, $password, $rememberMe = false)
    {
        // Setup
        $result = false;
        
        // Hash password
        $hashedPassword = $this->passwordCreate($password);
        
        // Attempt to grab user
        $users = $this->repo->findBy($this->authField, $authField);
        
        // If a single matching user was found, return it.
        if ($users->count() == 1) {
            
            // Grab user and set it as the return value.
            $user = $users->get(0);
            $result = $user;
            
            // Set a logged-in user in session.
            $this->session->user = $user->id;
            $this->session->secureLogin = true;
            $this->user = $user->id;
            $this->secureLogin = true;
            
            // Set cookie if necessary
            if ($rememberMe) {
                $this->setRememberMe($user);
            }
        }
        
        if ($this->redirect->isSaved()) {
            $this->redirect->gotoSaved();
            exit;
        }
        return $result;
    }
    
    
    public function logout()
    {
        // If a user is logged in, remove their token in the DB.
        // (to prevent passive cookie login on next visit)
        $userId = $this->session->user;
        if ($userId) {
            $user = $this->repo->find($userId);
            $user->token = null;
            $this->repo->save($user);
        }
         
        unset($this->user);
        unset($this->secureLogin);
        $this->session->unset('user');
        $this->session->unset('secureLogin');
        $this->cookie->unset('user');
        $this->cookie->unset('token');
    }
    
    
    /**
     *  For passiely logging in via a "rememberMe" cookie.
     */
    protected function insecureLogin($userId = false, $token = false)
    {
        if ($userId && $token) {
            
            // Try and grab user from DB.
            $user = $this->repo->find($userId);
        
            if ($user) {
                if ($user->token == $token) {
                    // Set a logged-in user in session.
                    $this->session->user = $user->id;
                    $this->session->secureLogin = false;
                    $this->user = $user->id;
                    $this->secureLogin = false;
                    return true;
                }
            }
        }
        return false; 
    }
    
    
    /**
     *  Create a token and save it on both the user's computer and the server.
     */
    protected function setRememberMe($user)
    {
        // Generate a new token.
        $toke = $this->tokenCreate();
        
        // Store token in cookie on user's machine.
        $this->cookie->user = $user->id;
        $this->cookie->token = $token;
        
        // Store token in the server's database for later comparison.
        $user->token = $token;
        
        // save the user.
        $this->repo->save($user);
    }
    
    
    /**
     *  Hash a password and return it.
     */
    protected function passwordCreate($plainTextPassword)
    {
        return password_hash($plainTextPassword, PASSWORD_DEFAULT);
    }
    
    
    /**
     *  Generate a hash from a random string and return it.
     */
    protected function tokenCreate()
    {
        return password_hash($this->randomString, PASSWORD_DEFAULT);
    }
    
    
    /**
     *  Return a random string.
     */ 
    protected function randomString($length = 50) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$string = '';

		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, (strlen($characters)-1))];
		}
		return $string;
	}
}